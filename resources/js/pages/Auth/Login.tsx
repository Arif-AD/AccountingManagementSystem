import { Form, Head } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function Login() {
    return (
        <main className="flex min-h-screen items-center justify-center bg-[#17352d] px-6 py-12 text-[#1c2823]">
            <Head title="Masuk" />
            <div className="w-full max-w-lg overflow-hidden rounded-2xl bg-[#f8faf6] shadow-2xl">
                <div className="p-8 lg:p-12">
                    <Head title="Masuk" />
                    <p className="text-sm font-semibold uppercase tracking-[0.16em] text-[#789083]">Selamat datang kembali</p>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight">Masuk ke Ledgerly</h1>
                    <p className="mt-2 text-sm text-[#617268]">Gunakan kredensial ruang kerja Anda untuk melanjutkan.</p>
                    <Form method="post" action="/login" className="mt-9 space-y-5">
                        {({ errors, processing }: { errors: Record<string, string>; processing: boolean }) => (
                            <>
                                <label className="block text-sm font-medium">Alamat email<input name="email" type="email" autoComplete="email" className="mt-2 block w-full rounded-lg border border-[#cad7cc] bg-white px-4 py-3 outline-none transition focus:border-[#17352d] focus:ring-2 focus:ring-[#d9e96d]" />{errors.email && <span className="mt-2 block text-xs text-red-700">{errors.email}</span>}</label>
                                <label className="block text-sm font-medium">Kata sandi<input name="password" type="password" autoComplete="current-password" className="mt-2 block w-full rounded-lg border border-[#cad7cc] bg-white px-4 py-3 outline-none transition focus:border-[#17352d] focus:ring-2 focus:ring-[#d9e96d]" />{errors.password && <span className="mt-2 block text-xs text-red-700">{errors.password}</span>}</label>
                                <label className="flex items-center gap-2 text-sm text-[#617268]"><input name="remember" type="checkbox" value="1" className="h-4 w-4 accent-[#17352d]" />Ingat saya</label>
                                <button disabled={processing} type="submit" className="w-full rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#285348] disabled:opacity-60">{processing ? 'Sedang masuk...' : 'Masuk'}</button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </main>
    );
}