import { FormEvent, useState } from 'react';
import { Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';

export default function Upload() {
    const [file, setFile] = useState<File | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!file) {
            setError('Please select a CSV file');
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch('/accounting/journal-upload', {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                const error = await response.json();
                setError(error.message || 'Upload failed. Please try again.');
                return;
            }

            window.location.href = '/accounting/journal-entries';
        } catch (err: any) {
            setError(err.message || 'Upload failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="mb-6">
                            <h1 className="text-3xl font-bold text-gray-900">Upload Jurnal</h1>
                            <p className="text-gray-600 mt-2">Import journal entries from a CSV file</p>
                        </div>

                        {error && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                                {error}
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    CSV File
                                </label>
                                <input
                                    type="file"
                                    accept=".csv,.txt"
                                    onChange={(e) => {
                                        setFile(e.target.files?.[0] || null);
                                        setError(null);
                                    }}
                                    disabled={loading}
                                    className="block w-full px-4 py-2 border border-gray-300 rounded-lg"
                                />
                                <p className="text-sm text-gray-500 mt-2">
                                    CSV format: date, description, account_code, debit, credit
                                </p>
                                <p className="text-sm text-gray-500 mt-1">
                                    Example: 2026-08-22, Pembelian laptop, 1100, 10000000, 0
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400"
                                >
                                    {loading ? 'Uploading...' : 'Upload'}
                                </button>
                                <Link
                                    href="/accounting/journal-entries"
                                    className="text-blue-600 hover:text-blue-700"
                                >
                                    Cancel
                                </Link>
                            </div>
                        </form>

                        <div className="mt-8 pt-8 border-t border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">CSV Format Guide</h2>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p className="text-sm text-gray-700 font-mono mb-3">
                                    date,description,account_code,debit,credit
                                </p>
                                <p className="text-sm text-gray-700 font-mono mb-3">
                                    2026-08-22,Pembelian laptop,1100,10000000,0
                                </p>
                                <p className="text-sm text-gray-700 font-mono">
                                    2026-08-22,Pembelian laptop,5100,0,10000000
                                </p>
                            </div>

                            <div className="mt-6 space-y-3 text-sm text-gray-700">
                                <p><strong>Rules:</strong></p>
                                <ul className="list-disc list-inside space-y-1">
                                    <li>Account codes must exist in Chart of Accounts and be active</li>
                                    <li>Minimum 2 journal lines required</li>
                                    <li>Total debit must equal total credit</li>
                                    <li>Must have at least one debit and one credit</li>
                                    <li>No negative amounts allowed</li>
                                    <li>A line cannot have both debit and credit</li>
                                    <li>Imported journals are created as draft status</li>
                                    <li>Journals must follow approval workflow: draft → pending → approved → posted</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
