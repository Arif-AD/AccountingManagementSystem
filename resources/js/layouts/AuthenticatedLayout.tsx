import { Form, Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { SharedPageProps } from '../types/index.ts';

const primaryNavigation = [
    { label: 'Dashboard', href: '/dashboard', active: true },
    { label: 'Chart of Accounts', href: '/accounting/chart-of-accounts', active: true },
];

const upcomingNavigation = [
    'Transactions',
    'Journal Upload',
    'General Ledger',
    'Trial Balance',
    'Balance Sheet',
    'Income Statement',
    'Financial Position',
];

export default function AuthenticatedLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const { auth } = page.props as any as SharedPageProps;
    const user = auth.user;

    return (
        <div className="min-h-screen bg-[#f4f6f1] text-[#1c2823]">
            <aside className="fixed inset-y-0 left-0 z-20 hidden w-72 flex-col bg-[#17352d] px-6 py-7 text-[#e9f1e8] lg:flex">
                <Link href="/dashboard" className="mb-12 flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#d9e96d] text-lg font-semibold text-[#17352d]">A</span>
                    <span>
                        <span className="block text-sm font-semibold tracking-wide">Ledgerly</span>
                        <span className="block text-xs text-[#a5bdb1]">Accounting workspace</span>
                    </span>
                </Link>

                <nav className="flex-1 space-y-8">
                    <div>
                        <p className="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#7f9c8e]">Workspace</p>
                        <div className="space-y-1">
                            {primaryNavigation.map((item) => (
                                <Link key={item.href} href={item.href} className="block rounded-lg px-3 py-2.5 text-sm text-[#dce8df] transition hover:bg-[#285348]">
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                    <div>
                        <p className="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#7f9c8e]">Accounting & reports</p>
                        <div className="space-y-1">
                            {upcomingNavigation.map((item) => (
                                <span key={item} className="block cursor-default px-3 py-2 text-sm text-[#759388]">{item}</span>
                            ))}
                        </div>
                    </div>
                </nav>

                <div className="border-t border-[#31574b] pt-5">
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#e8b06a] text-sm font-semibold text-[#17352d]">{user?.name.charAt(0)}</div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{user?.name}</p>
                            <p className="text-xs capitalize text-[#a5bdb1]">{user?.role}</p>
                        </div>
                        <Form method="post" action="/logout">
                            <button type="submit" title="Sign out" className="text-[#a5bdb1] hover:text-white">↗</button>
                        </Form>
                    </div>
                </div>
            </aside>

            <div className="lg:pl-72">
                <header className="flex h-20 items-center justify-between border-b border-[#dce4d9] bg-[#f8faf6] px-6 lg:px-10">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#789083]">Company workspace</p>
                        <p className="mt-1 text-sm text-[#53675d]">FY 2026 · Internal reporting</p>
                    </div>
                    <div className="flex items-center gap-4 lg:hidden">
                        <span className="text-right text-xs"><strong className="block">{user?.name}</strong><span className="capitalize text-[#789083]">{user?.role}</span></span>
                        <Form method="post" action="/logout"><button type="submit" className="text-sm font-semibold text-[#17352d]">Sign out</button></Form>
                    </div>
                </header>
                <main className="px-6 py-8 lg:px-10 lg:py-10">{children}</main>
            </div>
        </div>
    );
}