import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: (title: string) => `${title} - Accounting Management System`,
    resolve: (name: string) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob<Record<string, any>>(
                './pages/**/*.tsx',
            ),
        ),
    setup({ el, App, props }: any) {
        if (el) {
            createRoot(el).render(<App {...props} />);
        }
    },
} as any);