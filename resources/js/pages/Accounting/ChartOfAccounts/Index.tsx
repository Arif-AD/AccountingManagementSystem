import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { ChartOfAccount, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    accounts: ChartOfAccount[];
    canEdit: boolean;
}

const typeStyles: Record<ChartOfAccount['type'], string> = {
    asset: 'bg-[#e3eee1] text-[#386251]',
    liability: 'bg-[#f8e9d8] text-[#9b5e23]',
    equity: 'bg-[#e5e1f0] text-[#5d4b81]',
    revenue: 'bg-[#dcebf0] text-[#2f6577]',
    expense: 'bg-[#f5dfdf] text-[#994c4c]',
};

const typeLabels: Record<ChartOfAccount['type'], string> = {
    asset: 'Aset',
    liability: 'Kewajiban',
    equity: 'Ekuitas',
    revenue: 'Pendapatan',
    expense: 'Beban',
};

export default function ChartOfAccounts() {
    const page = usePage();
    const { accounts, canEdit } = page.props as any as Props;
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        name: '',
        type: 'asset',
        parent_id: '',
        description: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post('/accounting/chart-of-accounts', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="COA" />

            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col justify-between gap-5 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold text-[#c17d35]">Akuntansi</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">COA</h1>
                        <p className="mt-2 text-sm text-[#617268]">
                            Struktur akun yang akan mengatur setiap entri jurnal.
                        </p>
                    </div>

                    {canEdit && (
                        <button type="button" onClick={() => setShowForm(true)} className="w-fit rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#285348]">
                            + Tambah akun
                        </button>
                    )}
                </div>

                {showForm && canEdit && (
                    <div className="mt-8 border border-[#dce4d9] bg-[#f8faf6] p-6">
                        <div className="flex items-center justify-between">
                            <h2 className="text-xl font-semibold">Tambah akun</h2>
                            <button type="button" onClick={() => setShowForm(false)} className="text-sm font-semibold text-[#617268]">Tutup</button>
                        </div>
                        <form onSubmit={submit} className="mt-5 grid gap-4 sm:grid-cols-2">
                            <label className="text-sm font-medium">Kode akun<input value={data.code} onChange={(event) => setData('code', event.target.value)} className="mt-2 block w-full rounded-lg border-[#ccd8cb] bg-white" required />{errors.code && <span className="mt-1 block text-xs text-red-600">{errors.code}</span>}</label>
                            <label className="text-sm font-medium">Nama akun<input value={data.name} onChange={(event) => setData('name', event.target.value)} className="mt-2 block w-full rounded-lg border-[#ccd8cb] bg-white" required />{errors.name && <span className="mt-1 block text-xs text-red-600">{errors.name}</span>}</label>
                            <label className="text-sm font-medium">Tipe<select value={data.type} onChange={(event) => setData('type', event.target.value)} className="mt-2 block w-full rounded-lg border-[#ccd8cb] bg-white"><option value="asset">Aset</option><option value="liability">Kewajiban</option><option value="equity">Ekuitas</option><option value="revenue">Pendapatan</option><option value="expense">Beban</option></select></label>
                            <label className="text-sm font-medium">Akun induk<select value={data.parent_id} onChange={(event) => setData('parent_id', event.target.value)} className="mt-2 block w-full rounded-lg border-[#ccd8cb] bg-white"><option value="">Akun tingkat atas</option>{accounts.map((account) => <option key={account.id} value={account.id}>{account.code} - {account.name}</option>)}</select></label>
                            <label className="text-sm font-medium sm:col-span-2">Deskripsi<textarea value={data.description} onChange={(event) => setData('description', event.target.value)} className="mt-2 block w-full rounded-lg border-[#ccd8cb] bg-white" rows={3} /></label>
                            <div className="flex gap-3 sm:col-span-2"><button type="submit" disabled={processing} className="rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white disabled:opacity-60">{processing ? 'Menyimpan...' : 'Simpan akun'}</button><button type="button" onClick={() => setShowForm(false)} className="rounded-lg border border-[#b9cbb9] px-4 py-3 text-sm font-semibold text-[#386251]">Batal</button></div>
                        </form>
                    </div>
                )}

                <div className="mt-8 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]">
                            <tr>
                                <th className="px-5 py-4 font-semibold">Kode</th>
                                <th className="px-5 py-4 font-semibold">Nama akun</th>
                                <th className="px-5 py-4 font-semibold">Tipe</th>
                                <th className="px-5 py-4 font-semibold">Akun induk</th>
                                <th className="px-5 py-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[#e5ebe4]">
                            {accounts.map((account: ChartOfAccount) => (
                                <tr key={account.id} className="transition hover:bg-[#f0f5ed]">
                                    <td className="px-5 py-4 font-mono text-xs text-[#617268]">{account.code}</td>
                                    <td className="px-5 py-4 font-medium">{account.name}</td>
                                    <td className="px-5 py-4">
                                        <span className={`rounded-full px-2.5 py-1 text-xs font-medium capitalize ${typeStyles[account.type]}`}>
                                            {typeLabels[account.type]}
                                        </span>
                                    </td>
                                    <td className="px-5 py-4 text-[#617268]">{account.parent?.name ?? 'Akun tingkat atas'}</td>
                                    <td className="px-5 py-4">
                                        <span className="inline-flex items-center gap-2 text-xs text-[#386251]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-[#6d9e69]" />
                                            Aktif
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <p className="mt-4 text-xs text-[#789083]">
                    {accounts.length} akun yang sudah dibuat · Siap untuk alur jurnal di masa depan
                    {!canEdit && ' · (Hanya lihat)'}
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
