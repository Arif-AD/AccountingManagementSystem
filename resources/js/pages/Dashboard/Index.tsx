import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.tsx';
import type { SharedPageProps } from '../../types/index.ts';

interface DashboardProps extends SharedPageProps {
    summary: {
        transactions: number;
        revenue: number;
        expenses: number;
        netProfit: number;
    };
}

export default function Dashboard({ summary }: DashboardProps) {
    const page = usePage();
    const { auth } = page.props as any as SharedPageProps;
    const roleLabel = (role?: string) => ({ accountant: 'Akuntan', manager: 'Manajer', admin: 'Admin' }[role ?? ''] ?? role ?? 'Pengguna');
    const cards = [['Total transaksi', summary.transactions.toLocaleString(), 'Jurnal posted tahun ini'], ['Total pendapatan', `Rp ${summary.revenue.toLocaleString()}`, 'Dari jurnal posted'], ['Total pengeluaran', `Rp ${summary.expenses.toLocaleString()}`, 'Dari jurnal posted'], ['Laba bersih', `Rp ${summary.netProfit.toLocaleString()}`, 'Pendapatan dikurangi beban']];

    return <AuthenticatedLayout><Head title="Dasbor" /><div className="mx-auto max-w-7xl"><div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p className="text-sm font-semibold text-[#c17d35]">Ringkasan</p><h1 className="mt-2 text-3xl font-semibold tracking-tight lg:text-4xl">Selamat pagi, {auth.user?.name.split(' ')[0]}.</h1><p className="mt-2 text-sm text-[#617268]">Berikut gambaran ruang kerja akuntansi Anda.</p></div><span className="w-fit rounded-full bg-[#e3eee1] px-3 py-1.5 text-xs font-medium capitalize text-[#386251]">Akses {roleLabel(auth.user?.role)}</span></div><div className="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{cards.map(([label, value, note]) => <div key={label} className="border-t-2 border-[#b8cbb9] bg-[#f8faf6] p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[#789083]">{label}</p><p className="mt-5 text-3xl font-semibold tracking-tight">{value}</p><p className="mt-2 text-xs text-[#789083]">{note}</p></div>)}</div><section className="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]"><div className="min-h-64 bg-[#17352d] p-7 text-[#edf4ec]"><p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#9cb8a8]">Landasan hari 1</p><h2 className="mt-4 max-w-md text-2xl font-semibold leading-tight">Buku besar Anda siap untuk transaksi pertamanya.</h2><p className="mt-3 max-w-lg text-sm leading-6 text-[#b4c9ba]">Bagan akun dan akses berbasis peran sudah tersedia. Alur jurnal dan laporan akan dibangun di atas fondasi ini.</p></div><div className="border border-[#dce4d9] bg-[#f8faf6] p-7"><p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#789083]">Mulai cepat</p><h2 className="mt-3 text-xl font-semibold">Siapkan akun Anda</h2><p className="mt-2 text-sm leading-6 text-[#617268]">Tinjau akun yang sudah dibuat sebelum kerja entri jurnal dimulai.</p><a href="/accounting/chart-of-accounts" className="mt-6 inline-block text-sm font-semibold text-[#386251] underline decoration-[#d9e96d] decoration-2 underline-offset-4">View Chart of Accounts</a></div></section></div></AuthenticatedLayout>;
}