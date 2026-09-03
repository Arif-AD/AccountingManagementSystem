import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { JournalEntry, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    journal: JournalEntry & { lines: NonNullable<JournalEntry['lines']> };
}

const money = (value: string | number) => Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatStatus = (status: string) => ({
    draft: 'Draf',
    pending: 'Menunggu',
    approved: 'Disetujui',
    posted: 'Diposting',
}[status] ?? status);

export default function JournalEntriesShow() {
    const page = usePage();
    const { journal, auth } = page.props as any as Props;
    const errors = page.props.errors as Record<string, string>;
    const debit = journal.lines.reduce((sum, line) => sum + Number(line.debit), 0);
    const credit = journal.lines.reduce((sum, line) => sum + Number(line.credit), 0);
    const isAccountant = auth.user?.role === 'accountant';
    const isManager = auth.user?.role === 'manager';
    const canEdit = isAccountant && journal.status === 'draft';
    const canApprove = isManager && journal.status === 'pending';
    const canPost = isAccountant && journal.status === 'approved';
    const statusStyle = journal.status === 'draft' ? 'bg-[#fff1d8] text-[#9b5e23]' : journal.status === 'pending' ? 'bg-[#e5e1f0] text-[#5d4b81]' : journal.status === 'approved' ? 'bg-[#dcebf0] text-[#2f6577]' : 'bg-[#e3eee1] text-[#386251]';

    return (
        <AuthenticatedLayout>
            <Head title={journal.journal_number} />
            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col items-start justify-between gap-4 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold text-[#c17d35]">Detail jurnal</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{journal.journal_number}</h1>
                        <p className="mt-2 text-sm text-[#617268]">{journal.description || 'Tidak ada deskripsi'}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Link href="/accounting/journal-entries" className="text-sm font-semibold text-[#386251]">Kembali ke jurnal</Link>
                        {canEdit && <Link href={`/accounting/journal-entries/${journal.id}/edit`} className="rounded-lg border border-[#b9cbb9] px-4 py-2 text-sm font-semibold text-[#386251]">Edit</Link>}
                        {canEdit && <Form method="post" action={`/accounting/journal-entries/${journal.id}`} onSubmit={(event) => { if (!window.confirm('Hapus jurnal draf ini?')) event.preventDefault(); }}><input type="hidden" name="_method" value="delete" /><button type="submit" className="text-sm font-semibold text-[#994c4c]">Hapus</button></Form>}
                        {canEdit && <Form method="post" action={`/accounting/journal-entries/${journal.id}/submit`} onSubmit={(event) => { if (!window.confirm('Kirim jurnal ini untuk persetujuan manajer?')) event.preventDefault(); }}><button type="submit" className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white">Kirim untuk persetujuan</button></Form>}
                        {canApprove && <span className="text-sm text-[#617268]">Menunggu tindakan</span>}
                        {canPost && <Form method="post" action={`/accounting/journal-entries/${journal.id}/post`} onSubmit={(event) => { if (!window.confirm('Posting jurnal yang telah disetujui ini? Proses ini tidak dapat dibatalkan.')) event.preventDefault(); }}><button type="submit" className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white">Posting</button></Form>}
                    </div>
                </div>
                <div className="mt-8 grid gap-4 border border-[#dce4d9] bg-[#f8faf6] p-6 text-sm sm:grid-cols-5">
                    <div><p className="text-xs text-[#789083]">Tanggal</p><p className="mt-1 font-medium">{journal.transaction_date}</p></div>
                    <div><p className="text-xs text-[#789083]">Status</p><p className="mt-1"><span className={`rounded-full px-2.5 py-1 text-xs font-medium capitalize ${statusStyle}`}>{formatStatus(journal.status)}</span></p></div>
                    <div><p className="text-xs text-[#789083]">Dibuat oleh</p><p className="mt-1 font-medium">{journal.creator.name}</p></div>
                    <div><p className="text-xs text-[#789083]">Dibuat pada</p><p className="mt-1 font-medium">{new Date(journal.created_at).toLocaleString()}</p></div>
                    <div><p className="text-xs text-[#789083]">{journal.source === 'csv' ? 'File' : 'Sumber'}</p>{journal.source === 'csv' ? <a href={`/accounting/journal-entries/${journal.id}/file`} className="mt-2 inline-flex items-center gap-2 rounded-lg border border-[#b9cbb9] px-3 py-2 text-sm font-semibold text-[#386251] hover:bg-[#f0f5ed]"><Download size={16} aria-hidden="true" />Download</a> : <p className="mt-1 font-medium">Manual</p>}</div>
                    {journal.source !== 'csv' && <div><p className="text-xs text-[#789083]">Saldo</p><p className={`mt-1 font-semibold ${Math.abs(debit - credit) < 0.005 ? 'text-[#386251]' : 'text-[#994c4c]'}`}>{Math.abs(debit - credit) < 0.005 ? 'Seimbang' : 'Tidak seimbang'}</p></div>}
                </div>
                {errors?.file && <p className="mt-4 border border-red-200 bg-red-50 p-3 text-sm text-red-700">{errors.file}</p>}
                {journal.source !== 'csv' && <div className="mt-6 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]">
                    <table className="w-full min-w-[700px] text-left text-sm"><thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4">Akun</th><th className="px-5 py-4">Deskripsi</th><th className="px-5 py-4 text-right">Debit</th><th className="px-5 py-4 text-right">Kredit</th></tr></thead><tbody className="divide-y divide-[#e5ebe4]">{journal.lines.map((line) => <tr key={line.id}><td className="px-5 py-4"><span className="font-mono text-xs">{line.account.code}</span><span className="ml-3 font-medium">{line.account.name}</span></td><td className="px-5 py-4 text-[#617268]">{line.description || '-'}</td><td className="px-5 py-4 text-right">{money(line.debit)}</td><td className="px-5 py-4 text-right">{money(line.credit)}</td></tr>)}</tbody><tfoot className="border-t border-[#dce4d9] font-semibold"><tr><td colSpan={2} className="px-5 py-4 text-right">Total</td><td className="px-5 py-4 text-right">{money(debit)}</td><td className="px-5 py-4 text-right">{money(credit)}</td></tr></tfoot></table>
                </div>}
                {canApprove && <div className="mt-6 grid grid-cols-2 gap-3"><Form method="post" action={`/accounting/journal-entries/${journal.id}/approve`} onSubmit={(event) => { if (!window.confirm('Setujui jurnal ini?')) event.preventDefault(); }}><button type="submit" className="w-full rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white">Setujui</button></Form><Form method="post" action={`/accounting/journal-entries/${journal.id}/reject`} onSubmit={(event) => { if (!window.confirm('Tolak jurnal ini dan kembalikan ke draf?')) event.preventDefault(); }}><button type="submit" className="w-full rounded-lg border border-[#d9a7a7] px-4 py-3 text-sm font-semibold text-[#994c4c]">Tolak</button></Form></div>}
            </div>
        </AuthenticatedLayout>
    );
}
