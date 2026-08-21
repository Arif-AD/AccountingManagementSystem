import { Form, Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { JournalEntry, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    journal: JournalEntry & { lines: NonNullable<JournalEntry['lines']> };
}

const money = (value: string | number) => Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function JournalEntriesShow() {
    const page = usePage();
    const { journal, auth } = page.props as any as Props;
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
                        <p className="text-sm font-semibold text-[#c17d35]">Journal detail</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">{journal.journal_number}</h1>
                        <p className="mt-2 text-sm text-[#617268]">{journal.description || 'No description'}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Link href="/accounting/journal-entries" className="text-sm font-semibold text-[#386251]">Back to journals</Link>
                        {canEdit && <Link href={`/accounting/journal-entries/${journal.id}/edit`} className="rounded-lg border border-[#b9cbb9] px-4 py-2 text-sm font-semibold text-[#386251]">Edit</Link>}
                        {canEdit && <Form method="post" action={`/accounting/journal-entries/${journal.id}`} onSubmit={(event) => { if (!window.confirm('Delete this draft journal?')) event.preventDefault(); }}><input type="hidden" name="_method" value="delete" /><button type="submit" className="text-sm font-semibold text-[#994c4c]">Delete</button></Form>}
                        {canEdit && <Form method="post" action={`/accounting/journal-entries/${journal.id}/submit`} onSubmit={(event) => { if (!window.confirm('Submit this journal for manager approval?')) event.preventDefault(); }}><button type="submit" className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white">Submit for Approval</button></Form>}
                        {canApprove && <><Form method="post" action={`/accounting/journal-entries/${journal.id}/approve`} onSubmit={(event) => { if (!window.confirm('Approve this journal?')) event.preventDefault(); }}><button type="submit" className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white">Approve</button></Form><Form method="post" action={`/accounting/journal-entries/${journal.id}/reject`} onSubmit={(event) => { if (!window.confirm('Reject this journal and return it to draft?')) event.preventDefault(); }}><button type="submit" className="text-sm font-semibold text-[#994c4c]">Reject</button></Form></>}
                        {canPost && <Form method="post" action={`/accounting/journal-entries/${journal.id}/post`} onSubmit={(event) => { if (!window.confirm('Post this approved journal? Posting is irreversible.')) event.preventDefault(); }}><button type="submit" className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white">Post</button></Form>}
                    </div>
                </div>
                <div className="mt-8 grid gap-4 border border-[#dce4d9] bg-[#f8faf6] p-6 text-sm sm:grid-cols-5">
                    <div><p className="text-xs text-[#789083]">Date</p><p className="mt-1 font-medium">{journal.transaction_date}</p></div>
                    <div><p className="text-xs text-[#789083]">Status</p><p className="mt-1"><span className={`rounded-full px-2.5 py-1 text-xs font-medium capitalize ${statusStyle}`}>{journal.status}</span></p></div>
                    <div><p className="text-xs text-[#789083]">Created by</p><p className="mt-1 font-medium">{journal.creator.name}</p></div>
                    <div><p className="text-xs text-[#789083]">Created at</p><p className="mt-1 font-medium">{new Date(journal.created_at).toLocaleString()}</p></div>
                    <div><p className="text-xs text-[#789083]">Balance</p><p className={`mt-1 font-semibold ${Math.abs(debit - credit) < 0.005 ? 'text-[#386251]' : 'text-[#994c4c]'}`}>{Math.abs(debit - credit) < 0.005 ? 'Balanced' : 'Unbalanced'}</p></div>
                </div>
                <div className="mt-6 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]">
                    <table className="w-full min-w-[700px] text-left text-sm"><thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4">Account</th><th className="px-5 py-4">Description</th><th className="px-5 py-4 text-right">Debit</th><th className="px-5 py-4 text-right">Credit</th></tr></thead><tbody className="divide-y divide-[#e5ebe4]">{journal.lines.map((line) => <tr key={line.id}><td className="px-5 py-4"><span className="font-mono text-xs">{line.account.code}</span><span className="ml-3 font-medium">{line.account.name}</span></td><td className="px-5 py-4 text-[#617268]">{line.description || '-'}</td><td className="px-5 py-4 text-right">{money(line.debit)}</td><td className="px-5 py-4 text-right">{money(line.credit)}</td></tr>)}</tbody><tfoot className="border-t border-[#dce4d9] font-semibold"><tr><td colSpan={2} className="px-5 py-4 text-right">Totals</td><td className="px-5 py-4 text-right">{money(debit)}</td><td className="px-5 py-4 text-right">{money(credit)}</td></tr></tfoot></table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
