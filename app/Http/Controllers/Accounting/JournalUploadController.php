<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JournalUploadController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Accounting/JournalEntries/Upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $hasPendingUpload = JournalEntry::query()
            ->where('created_by', auth()->id())
            ->where('source', 'csv')
            ->where('status', JournalEntry::STATUS_PENDING)
            ->exists();

        if ($hasPendingUpload) {
            throw ValidationException::withMessages([
                'file' => 'Masih ada file unggahan yang berstatus pending. Tunggu persetujuan manager sebelum mengunggah file baru.',
            ]);
        }

        $uploadedFile = $request->file('file');
        if ($uploadedFile && $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $message = match ($uploadedFile->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File terlalu besar. Batas upload server saat ini 2 MB.',
                UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian. Silakan coba lagi.',
                UPLOAD_ERR_NO_FILE => 'Silakan pilih file CSV atau XLSX terlebih dahulu.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload PHP tidak tersedia. Periksa konfigurasi upload_tmp_dir pada server.',
                UPLOAD_ERR_CANT_WRITE => 'PHP tidak dapat menulis file upload ke disk. Periksa izin folder temporary server.',
                UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP pada server.',
                default => 'File gagal diunggah oleh server (kode error '.$uploadedFile->getError().').',
            };

            throw ValidationException::withMessages(['file' => $message]);
        }

        $file = $request->validate(['file' => ['required', 'file', 'max:2048']])['file'];
        if ($file->getSize() === 0) {
            throw ValidationException::withMessages([
                'file' => 'File yang dipilih kosong atau tidak berhasil dibaca oleh server.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Format file harus CSV, XLSX, atau XLS.',
            ]);
        }

        $path = Storage::disk('local')->putFile('journal-uploads', $file);
        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'File tidak dapat disimpan di penyimpanan lokal. Pastikan folder storage/app/private dapat ditulis.',
            ]);
        }

        $journal = DB::transaction(function () use ($file, $path) {
            $last = JournalEntry::lockForUpdate()->latest('id')->value('journal_number');
            $number = $last ? ((int) substr($last, 3)) + 1 : 1;

            return JournalEntry::create([
                'journal_number' => 'JV-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                'transaction_date' => now()->toDateString(),
                'description' => 'Upload file',
                'source' => 'csv',
                'original_file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => JournalEntry::STATUS_PENDING,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('accounting.journal-entries.show', $journal);
    }
}
