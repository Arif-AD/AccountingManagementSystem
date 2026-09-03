<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JournalEntryController extends Controller
{
    public function index(): Response
    {
        $filters = request()->validate([
            'status' => ['nullable', 'in:draft,pending,approved,posted'],
            'date' => ['nullable', 'date'],
            'journal_number' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $query = JournalEntry::query()
            ->with('creator:id,name')
            ->withSum('lines', 'debit')
            ->withSum('lines', 'credit')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        $query->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', $date));
        $query->when($filters['journal_number'] ?? null, fn ($query, $number) => $query->where('journal_number', 'like', "%{$number}%"));
        $query->when($filters['description'] ?? null, fn ($query, $description) => $query->where('description', 'like', "%{$description}%"));

        return Inertia::render('Accounting/JournalEntries/Index', [
            'journals' => $query
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/JournalEntries/Create', [
            'accounts' => ChartOfAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
        ]);

        $validated['lines'] = self::validateLines($request->validate([
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['required', 'numeric', 'gte:0'],
            'lines.*.credit' => ['required', 'numeric', 'gte:0'],
        ])['lines']);

        $journalEntry = DB::transaction(function () use ($validated) {
            $lastNumber = JournalEntry::query()
                ->lockForUpdate()
                ->latest('id')
                ->value('journal_number');
            $nextNumber = $lastNumber ? ((int) substr($lastNumber, 3)) + 1 : 1;

            $journalEntry = JournalEntry::create([
                'journal_number' => 'JV-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT),
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? null,
                'source' => 'manual',
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $journalEntry->lines()->createMany($validated['lines']);

            return $journalEntry;
        });

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function edit(JournalEntry $journalEntry): Response
    {
        $this->ensureDraft($journalEntry);

        return Inertia::render('Accounting/JournalEntries/Edit', [
            'journal' => $journalEntry->load('lines.account:id,code,name'),
            'accounts' => ChartOfAccount::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->ensureDraft($journalEntry);
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
        ]);
        $validated['lines'] = self::validateLines($request->validate([
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['required', 'numeric', 'gte:0'],
            'lines.*.credit' => ['required', 'numeric', 'gte:0'],
        ])['lines']);

        DB::transaction(function () use ($journalEntry, $validated) {
            $journalEntry->update([
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? null,
            ]);
            $journalEntry->lines()->delete();
            $journalEntry->lines()->createMany($validated['lines']);
        });

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->ensureDraft($journalEntry);
        DB::transaction(fn () => $journalEntry->delete());

        return redirect()->route('accounting.journal-entries.index');
    }

    public function post(JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($journalEntry->isApproved(), 403, 'Only approved journals can be posted.');
        $this->validatedLinesForEntry($journalEntry);

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_POSTED]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function submit(JournalEntry $journalEntry): RedirectResponse
    {
        $this->ensureDraft($journalEntry);
        $this->validatedLinesForEntry($journalEntry);

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_PENDING]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function approve(JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($journalEntry->isPending(), 403, 'Only pending journals can be approved.');

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_APPROVED]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function reject(JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($journalEntry->isPending(), 403, 'Only pending journals can be rejected.');

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_DRAFT]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function show(JournalEntry $journalEntry): Response
    {
        return Inertia::render('Accounting/JournalEntries/Show', [
            'journal' => $journalEntry->load([
                'creator:id,name',
                'lines.account:id,code,name',
            ]),
        ]);
    }

    public function downloadFile(JournalEntry $journalEntry)
    {
        abort_unless($journalEntry->source === 'csv' && $journalEntry->file_path, 404);

        return response()->download(storage_path('app/private/'.$journalEntry->file_path), $journalEntry->original_file_name);
    }

    private function ensureDraft(JournalEntry $journalEntry): void
    {
        abort_unless($journalEntry->isDraft(), 403, 'Posted journals are immutable.');
    }

    public static function validateLines(array $lines): array
    {
        $totalDebit = '0.00';
        $totalCredit = '0.00';
        $hasDebit = false;
        $hasCredit = false;
        foreach ($lines as $index => $line) {
            $debit = number_format((float) $line['debit'], 2, '.', '');
            $credit = number_format((float) $line['credit'], 2, '.', '');
            if (bccomp($debit, '0.00', 2) > 0 && bccomp($credit, '0.00', 2) > 0) {
                throw ValidationException::withMessages(["lines.$index.debit" => 'A line cannot contain both debit and credit.']);
            }
            if (bccomp($debit, '0.00', 2) === 0 && bccomp($credit, '0.00', 2) === 0) {
                throw ValidationException::withMessages(["lines.$index.debit" => 'Each line must contain a positive debit or credit amount.']);
            }
            $hasDebit = $hasDebit || bccomp($debit, '0.00', 2) > 0;
            $hasCredit = $hasCredit || bccomp($credit, '0.00', 2) > 0;
            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }
        if (! $hasDebit || ! $hasCredit || bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages(['lines' => 'The journal must have debit and credit totals that are equal.']);
        }
        $accountIds = collect($lines)->pluck('chart_of_account_id')->unique();
        if (ChartOfAccount::whereIn('id', $accountIds)->where('is_active', true)->count() !== $accountIds->count()) {
            throw ValidationException::withMessages(['lines' => 'Every journal line must reference an active Chart of Account.']);
        }

        return $lines;
    }

    private function validatedLinesForEntry(JournalEntry $journalEntry): void
    {
        self::validateLines($journalEntry->lines->toArray());
    }

    private function parseCsvLinesForApproval(JournalEntry $journalEntry): void
    {
        if (! $journalEntry->file_path) {
            throw ValidationException::withMessages(['file' => 'Attachment file tidak ditemukan.']);
        }

        $path = storage_path('app/private/'.$journalEntry->file_path);
        if (! is_file($path) || filesize($path) === 0) {
            throw ValidationException::withMessages(['file' => 'File unggahan kosong atau tidak dapat dibaca. Silakan unggah ulang file tersebut.']);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $rows = $this->readCsvRows($path);
        } elseif ($extension === 'xlsx') {
            $rows = $this->readXlsxRows($path);
        } elseif ($extension === 'xls') {
            $rows = $this->readSpreadsheetRows($path);
        } else {
            throw ValidationException::withMessages(['file' => 'Format file harus CSV, XLSX, atau XLS.']);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi data jurnal yang bisa diproses.']);
        }

        $lines = [];
        foreach ($rows as $row) {
            $accountModel = ChartOfAccount::query()
                ->where('is_active', true)
                ->where(function ($query) use ($row) {
                    $query->where('code', trim((string) $row['account']))
                        ->orWhere('name', trim((string) $row['account']));
                })
                ->first();

            if (! $accountModel) {
                throw ValidationException::withMessages(['file' => "Akun '{$row['account']}' tidak ditemukan atau tidak aktif."]);
            }

            $debitValue = (float) $row['debit'];
            $creditValue = (float) $row['credit'];
            if (! is_numeric($row['debit']) || ! is_numeric($row['credit']) || $debitValue < 0 || $creditValue < 0) {
                throw ValidationException::withMessages(['file' => "Kolom debit/kredit untuk akun '{$row['account']}' harus angka non-negatif."]);
            }

            $lines[] = [
                'chart_of_account_id' => $accountModel->id,
                'description' => $row['description'],
                'debit' => number_format($debitValue, 2, '.', ''),
                'credit' => number_format($creditValue, 2, '.', ''),
            ];
        }

        $journalEntry->update([
            'transaction_date' => $rows[0]['date'],
            'description' => $rows[0]['description'] ?: 'Imported from spreadsheet',
        ]);
        $journalEntry->lines()->delete();
        $journalEntry->lines()->createMany(self::validateLines($lines));
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak bisa dibuka.']);
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'CSV tidak berisi header kolom.']);
        }

        $normalizedHeaders = array_map(function (string $header): string {
            $value = strtolower(trim($header));

            return preg_replace('/[^a-z0-9]+/u', '_', $value) ?? $value;
        }, $headerRow);

        $map = $this->mapRequiredColumns($normalizedHeaders);
        if ($map['date'] === null || $map['account'] === null || $map['debit'] === null || $map['credit'] === null) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'CSV harus memiliki kolom tanggal, akun/kode akun, debit, dan kredit.']);
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || array_filter($row, fn ($cell) => trim((string) $cell) !== '') === []) {
                continue;
            }

            $date = trim((string) ($row[$map['date']] ?? ''));
            $account = trim((string) ($row[$map['account']] ?? ''));
            $debit = trim((string) ($row[$map['debit']] ?? ''));
            $credit = trim((string) ($row[$map['credit']] ?? ''));

            if ($date === '' || $account === '') {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'account' => $account,
                'description' => trim((string) ($row[$map['description'] ?? 0] ?? '')) ?: null,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new \ZipArchive();
        $opened = $zip->open($path);
        if ($opened !== true) {
            throw ValidationException::withMessages(['file' => 'File Excel (.xlsx) tidak bisa dibuka.']);
        }

        $sharedStrings = [];
        $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringXml !== false) {
            $sharedXml = new \SimpleXMLElement($sharedStringXml);
            foreach ($sharedXml->si as $si) {
                $text = '';
                foreach ($si->t as $token) {
                    $text .= (string) $token;
                }
                $sharedStrings[] = $text;
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw ValidationException::withMessages(['file' => 'Tidak ada sheet Excel yang bisa dibaca.']);
        }

        $dom = new \DOMDocument();
        $dom->loadXML($sheetXml);

        $rows = [];
        foreach ($dom->getElementsByTagName('row') as $rowNode) {
            $values = [];
            foreach ($rowNode->childNodes as $cellNode) {
                if ($cellNode->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $reference = $cellNode->getAttribute('r');
                $column = 'A';
                if ($reference !== '') {
                    preg_match('/([A-Z]+)/', $reference, $matches);
                    $column = $matches[1] ?? 'A';
                }

                $columnIndex = $this->columnToIndex($column);
                $cellType = $cellNode->getAttribute('t');
                $cellValue = '';

                foreach ($cellNode->childNodes as $node) {
                    if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === 'v') {
                        $cellValue = trim((string) $node->textContent);
                    }
                    if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === 'is') {
                        $cellValue = trim((string) $node->textContent);
                    }
                }

                if ($cellType === 's' && $cellValue !== '') {
                    $cellValue = $sharedStrings[(int) $cellValue] ?? $cellValue;
                }

                $values[$columnIndex] = $cellValue;
            }

            if ($values === []) {
                continue;
            }

            ksort($values);
            $rows[] = array_values($values);
        }

        if ($rows === []) {
            return [];
        }

        $headerRow = array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $rows[0]);

        $normalizedHeaders = array_map(function ($header) {
            return preg_replace('/[^a-z0-9]+/u', '_', $header) ?? $header;
        }, $headerRow);

        $map = $this->mapRequiredColumns($normalizedHeaders);
        if ($map['date'] === null || $map['account'] === null || $map['debit'] === null || $map['credit'] === null) {
            throw ValidationException::withMessages(['file' => 'File Excel harus memiliki kolom tanggal, akun/kode akun, debit, dan kredit.']);
        }

        $result = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if ($row === [null] || array_filter($row, fn ($cell) => trim((string) $cell) !== '') === []) {
                continue;
            }

            $date = trim((string) ($row[$map['date']] ?? ''));
            $account = trim((string) ($row[$map['account']] ?? ''));
            $debit = trim((string) ($row[$map['debit']] ?? ''));
            $credit = trim((string) ($row[$map['credit']] ?? ''));

            if ($date === '' || $account === '') {
                continue;
            }

            $result[] = [
                'date' => $date,
                'account' => $account,
                'description' => trim((string) ($row[$map['description'] ?? 0] ?? '')) ?: null,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return $result;
    }

    private function readSpreadsheetRows(string $path): array
    {
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'File Excel (.xls) tidak bisa dibuka.']);
        }

        if ($rows === []) {
            return [];
        }

        $normalizedHeaders = array_map(function ($header) {
            $value = strtolower(trim((string) $header));

            return preg_replace('/[^a-z0-9]+/u', '_', $value) ?? $value;
        }, $rows[0]);
        $map = $this->mapRequiredColumns($normalizedHeaders);

        if ($map['date'] === null || $map['account'] === null || $map['debit'] === null || $map['credit'] === null) {
            throw ValidationException::withMessages(['file' => 'File Excel harus memiliki kolom tanggal, akun/kode akun, debit, dan kredit.']);
        }

        $result = [];
        foreach (array_slice($rows, 1) as $row) {
            $date = trim((string) ($row[$map['date']] ?? ''));
            $account = trim((string) ($row[$map['account']] ?? ''));
            if ($date === '' || $account === '') {
                continue;
            }

            $result[] = [
                'date' => $date,
                'account' => $account,
                'description' => trim((string) ($row[$map['description'] ?? 0] ?? '')) ?: null,
                'debit' => trim((string) ($row[$map['debit']] ?? '')),
                'credit' => trim((string) ($row[$map['credit']] ?? '')),
            ];
        }

        return $result;
    }

    private function mapRequiredColumns(array $headers): array
    {
        $result = ['date' => null, 'account' => null, 'debit' => null, 'credit' => null, 'description' => null];

        foreach ($headers as $index => $header) {
            if ($result['date'] === null && in_array($header, ['date', 'tanggal', 'transaction_date', 'trx_date'], true)) {
                $result['date'] = $index;
            }
            if ($result['account'] === null && in_array($header, ['account', 'akun', 'account_code', 'kode_akun', 'kode', 'code', 'account_name', 'nama_akun', 'name'], true)) {
                $result['account'] = $index;
            }
            if ($result['debit'] === null && in_array($header, ['debit', 'debet'], true)) {
                $result['debit'] = $index;
            }
            if ($result['credit'] === null && in_array($header, ['credit', 'kredit'], true)) {
                $result['credit'] = $index;
            }
            if ($result['description'] === null && in_array($header, ['description', 'deskripsi', 'memo', 'keterangan'], true)) {
                $result['description'] = $index;
            }
        }

        return $result;
    }

    private function columnToIndex(string $column): int
    {
        $value = 0;
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($column));
        if ($letters === '') {
            return 0;
        }

        foreach (str_split($letters) as $char) {
            $value = ($value * 26) + (ord($char) - 64);
        }

        return $value - 1;
    }
}