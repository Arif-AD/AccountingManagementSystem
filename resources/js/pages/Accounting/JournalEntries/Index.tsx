import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { JournalEntry, Paginated, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    journals: Paginated<JournalEntry>;
    filters: { status?: string; date?: string; journal_number?: string; description?: string };
}

const money = (value: string | number) => Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function JournalEntriesIndex() {
    const page = usePage();
    const { journals, auth, filters } = page.props as any as Props;
    const canCreate = auth.user?.role === 'accountant';
    const applyFilters = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/accounting/journal-entries', Object.fromEntries(form.entries()), { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Journal Entries" />
            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col justify-between gap-5 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold text-[#c17d35]">Accounting</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">Journal Entries</h1>
                        <p className="mt-2 text-sm text-[#617268]">Review balanced journal activity across the workspace.</p>
                    </div>
                    {canCreate && <Link href="/accounting/journal-entries/create" className="w-fit rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white hover:bg-[#285348]">+ Create journal</Link>}
                </div>

                <form onSubmit={applyFilters} className="mt-8 grid gap-3 border border-[#dce4d9] bg-[#f8faf6] p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <input name="journal_number" defaultValue={filters.journal_number ?? ''} placeholder="Journal number" className="rounded-lg border-[#ccd8cb] bg-white text-sm" />
                    <input name="date" type="date" defaultValue={filters.date ?? ''} className="rounded-lg border-[#ccd8cb] bg-white text-sm" />
                    <input name="description" defaultValue={filters.description ?? ''} placeholder="Description" className="rounded-lg border-[#ccd8cb] bg-white text-sm" />
                    <select name="status" defaultValue={filters.status ?? ''} className="rounded-lg border-[#ccd8cb] bg-white text-sm"><option value="">All statuses</option><option value="draft">Draft</option><option value="posted">Posted</option></select>
                    <button type="submit" className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white">Filter</button>
                </form>
                <div className="mt-4 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]">
                    <table className="w-full min-w-[850px] text-left text-sm">
                        <thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4">Journal number</th><th className="px-5 py-4">Date</th><th className="px-5 py-4">Description</th><th className="px-5 py-4">Total debit</th><th className="px-5 py-4">Total credit</th><th className="px-5 py-4">Status</th><th className="px-5 py-4">Created by</th><th className="px-5 py-4">Action</th></tr></thead>
                        <tbody className="divide-y divide-[#e5ebe4]">
                            {journals.data.map((journal) => <tr key={journal.id} className="hover:bg-[#f0f5ed]"><td className="px-5 py-4 font-mono text-xs font-semibold">{journal.journal_number}</td><td className="px-5 py-4 text-[#617268]">{journal.transaction_date}</td><td className="max-w-[220px] truncate px-5 py-4">{journal.description || 'No description'}</td><td className="px-5 py-4 font-medium">{money(journal.lines_sum_debit ?? 0)}</td><td className="px-5 py-4 font-medium">{money(journal.lines_sum_credit ?? 0)}</td><td className="px-5 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-medium capitalize ${journal.status === 'draft' ? 'bg-[#fff1d8] text-[#9b5e23]' : journal.status === 'pending' ? 'bg-[#e5e1f0] text-[#5d4b81]' : journal.status === 'approved' ? 'bg-[#dcebf0] text-[#2f6577]' : 'bg-[#e3eee1] text-[#386251]'}`}>{journal.status}</span></td><td className="px-5 py-4 text-[#617268]">{journal.creator.name}</td><td className="px-5 py-4"><Link href={`/accounting/journal-entries/${journal.id}`} className="font-semibold text-[#386251] hover:underline">View</Link></td></tr>)}
                        </tbody>
                    </table>
                </div>
                <p className="mt-4 text-xs text-[#789083]">{journals.total} journal entries</p>
            </div>
        </AuthenticatedLayout>
    );
}