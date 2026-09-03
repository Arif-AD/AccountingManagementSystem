import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';

export default function Upload() {
    const { data, setData, post, processing, errors } = useForm<{ file: File | null }>({ file: null });
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post('/accounting/journal-entries/upload', { forceFormData: true });
    };
    return <AuthenticatedLayout><Head title="Unggah Jurnal" /><div className="mx-auto max-w-3xl"><div className="border border-[#dce4d9] bg-[#f8faf6] p-6"><div className="flex items-center justify-between"><div><p className="text-sm font-semibold text-[#c17d35]">Entri Jurnal</p><h1 className="mt-2 text-3xl font-semibold">Unggah Jurnal</h1></div><Link href="/accounting/journal-entries" className="text-sm font-semibold text-[#386251]">Kembali</Link></div>{errors.file && <p className="mt-6 border border-red-200 bg-red-50 p-3 text-sm text-red-700">{errors.file}</p>}<form onSubmit={submit} className="mt-8 space-y-5"><input type="file" accept=".csv,.xlsx,.xls" onChange={(event) => setData('file', event.target.files?.[0] ?? null)} disabled={processing} className="block w-full rounded-lg border-[#ccd8cb] bg-white text-sm" /><p className="text-sm text-[#617268]">File yang diterima: CSV, Excel (.xlsx), dan Excel lama (.xls). Batas saat ini 2 MB.</p><button disabled={processing} className="rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white disabled:opacity-60">{processing ? 'Mengunggah...' : 'Unggah Jurnal'}</button></form></div></div></AuthenticatedLayout>;
}
