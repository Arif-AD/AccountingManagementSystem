import { Form, Head } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function Login() {
    return (
        <main className="flex min-h-screen items-center justify-center bg-[#17352d] px-6 py-12 text-[#1c2823]">
            <Head title="Sign in" />
            <div className="grid w-full max-w-5xl overflow-hidden rounded-2xl bg-[#f8faf6] shadow-2xl lg:grid-cols-[1fr_1.1fr]">
                <div className="flex flex-col justify-between bg-[#d9e96d] p-8 lg:p-12">
                    <div><span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#17352d] text-lg font-semibold text-[#d9e96d]">A</span><p className="mt-8 max-w-xs text-4xl font-semibold leading-tight tracking-tight">Numbers that tell the whole story.</p></div>
                    <p className="mt-12 max-w-xs text-sm leading-6 text-[#456050]">A considered workspace for clean books, confident decisions, and a clearer view of the business.</p>
                </div>
                <div className="p-8 lg:p-12">
                    <Head title="Sign in" />
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#789083]">Welcome back</p>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight">Sign in to Ledgerly</h1>
                    <p className="mt-2 text-sm text-[#617268]">Use your workspace credentials to continue.</p>
                    <Form method="post" action="/login" className="mt-9 space-y-5">
                        {({ errors, processing }) => (
                            <>
                                <label className="block text-sm font-medium">Email address<input name="email" type="email" autoComplete="email" className="mt-2 block w-full rounded-lg border border-[#cad7cc] bg-white px-4 py-3 outline-none transition focus:border-[#17352d] focus:ring-2 focus:ring-[#d9e96d]" />{errors.email && <span className="mt-2 block text-xs text-red-700">{errors.email}</span>}</label>
                                <label className="block text-sm font-medium">Password<input name="password" type="password" autoComplete="current-password" className="mt-2 block w-full rounded-lg border border-[#cad7cc] bg-white px-4 py-3 outline-none transition focus:border-[#17352d] focus:ring-2 focus:ring-[#d9e96d]" />{errors.password && <span className="mt-2 block text-xs text-red-700">{errors.password}</span>}</label>
                                <label className="flex items-center gap-2 text-sm text-[#617268]"><input name="remember" type="checkbox" value="1" className="h-4 w-4 accent-[#17352d]" />Remember me</label>
                                <button disabled={processing} type="submit" className="w-full rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#285348] disabled:opacity-60">{processing ? 'Signing in...' : 'Sign in'}</button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </main>
    );
}