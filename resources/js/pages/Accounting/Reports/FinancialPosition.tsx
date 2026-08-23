import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { StatementAccount } from '../../../types/index.ts';

interface Props {
    assets: StatementAccount[];
    liabilities: StatementAccount[];
    equity: StatementAccount[];
    netIncome: number;
    totalAssets: number;
    totalLiabilities: number;
    totalEquity: number;
    balanced: boolean;
    filters: { start_date: string; end_date: string };
}

export default function FinancialPosition({
    assets,
    liabilities,
    equity,
    netIncome,
    totalAssets,
    totalLiabilities,
    totalEquity,
    balanced,
    filters,
}: Props) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const url = new URL(window.location.href);
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        window.location.href = url.toString();
    };

    return (
        <AuthenticatedLayout>
            <Head title="Financial Position" />
            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="mb-6">
                            <h1 className="text-3xl font-bold text-gray-900">Laporan Posisi Keuangan</h1>
                            <p className="text-gray-600 mt-1">Financial Position Report</p>
                        </div>

                        <form onSubmit={handleFilter} className="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Start Date
                                    </label>
                                    <input
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        End Date
                                    </label>
                                    <input
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    />
                                </div>
                                <div className="flex items-end">
                                    <button
                                        type="submit"
                                        className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                    >
                                        Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {/* Assets Section */}
                            <div>
                                <h2 className="text-xl font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">
                                    Aset (Assets)
                                </h2>
                                {assets.length === 0 ? (
                                    <p className="text-gray-500 text-sm">No assets</p>
                                ) : (
                                    <div className="space-y-2">
                                        {assets.map((account) => (
                                            <div key={account.id} className="flex justify-between text-sm">
                                                <span className="text-gray-700">
                                                    {account.code} - {account.name}
                                                </span>
                                                <span className="text-gray-900 font-medium">
                                                    {account.balance.toLocaleString('id-ID', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    })}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                <div className="mt-4 pt-4 border-t-2 border-green-300">
                                    <div className="flex justify-between font-bold text-green-700">
                                        <span>Total Aset</span>
                                        <span>
                                            {totalAssets.toLocaleString('id-ID', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Liabilities & Equity Section */}
                            <div>
                                <h2 className="text-xl font-bold text-gray-900 mb-4 pb-2 border-b-2 border-orange-600">
                                    Liabilitas & Ekuitas (Liabilities & Equity)
                                </h2>

                                {/* Liabilities */}
                                <div className="mb-6">
                                    <h3 className="text-lg font-semibold text-gray-800 mb-2">Liabilitas</h3>
                                    {liabilities.length === 0 ? (
                                        <p className="text-gray-500 text-sm">No liabilities</p>
                                    ) : (
                                        <div className="space-y-2 ml-2">
                                            {liabilities.map((account) => (
                                                <div key={account.id} className="flex justify-between text-sm">
                                                    <span className="text-gray-700">
                                                        {account.code} - {account.name}
                                                    </span>
                                                    <span className="text-gray-900 font-medium">
                                                        {account.balance.toLocaleString('id-ID', {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        })}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                {/* Equity */}
                                <div className="mb-6">
                                    <h3 className="text-lg font-semibold text-gray-800 mb-2">Ekuitas</h3>
                                    {equity.length === 0 ? (
                                        <p className="text-gray-500 text-sm">No equity accounts</p>
                                    ) : (
                                        <div className="space-y-2 ml-2">
                                            {equity.map((account) => (
                                                <div key={account.id} className="flex justify-between text-sm">
                                                    <span className="text-gray-700">
                                                        {account.code} - {account.name}
                                                    </span>
                                                    <span className="text-gray-900 font-medium">
                                                        {account.balance.toLocaleString('id-ID', {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        })}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                {/* Net Income */}
                                <div className="mb-4 pl-2 border-l-2 border-blue-400">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-700">Laba/(Rugi) Periode</span>
                                        <span className={`font-medium ${netIncome >= 0 ? 'text-green-700' : 'text-red-700'}`}>
                                            {netIncome.toLocaleString('id-ID', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}
                                        </span>
                                    </div>
                                </div>

                                <div className="mt-4 pt-4 border-t-2 border-orange-300">
                                    <div className="flex justify-between font-bold text-orange-700">
                                        <span>Total Liabilitas + Ekuitas</span>
                                        <span>
                                            {totalEquity.toLocaleString('id-ID', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2,
                                            })}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Balance Status */}
                        <div className="mt-8 p-4 rounded-lg" style={{
                            backgroundColor: balanced ? '#f0fdf4' : '#fef2f2',
                            borderLeft: `4px solid ${balanced ? '#22c55e' : '#ef4444'}`,
                        }}>
                            <div className="flex items-center justify-between">
                                <span className={`font-bold ${balanced ? 'text-green-700' : 'text-red-700'}`}>
                                    {balanced ? '✓ Balanced' : '✗ Not Balanced'}
                                </span>
                                <span className="text-sm text-gray-600">
                                    Difference: {Math.abs(totalAssets - totalEquity).toLocaleString('id-ID', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    })}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
