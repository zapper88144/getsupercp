import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FlashMessages() {
    const { flash } = usePage().props as any;
    const [show, setShow] = useState(false);

    useEffect(() => {
        if (flash.success || flash.error || flash.message) {
            setShow(true);
            const timer = setTimeout(() => setShow(false), 10000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    if (!show) return null;

    return (
        <div className="fixed bottom-4 right-4 z-50 max-w-md space-y-2">
            {flash.success && (
                <div className="flex items-start rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 shadow-lg dark:border-green-900 dark:bg-green-900/30 dark:text-green-400">
                    <div className="flex-shrink-0">
                        <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                        </svg>
                    </div>
                    <div className="ml-3 flex-1">
                        <p className="text-sm font-medium">{flash.success}</p>
                    </div>
                    <button onClick={() => setShow(false)} className="ml-4 inline-flex text-green-500 hover:text-green-600">
                        <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                    </button>
                </div>
            )}

            {flash.error && (
                <div className="flex flex-col rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 shadow-lg dark:border-red-900 dark:bg-red-900/30 dark:text-red-400">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ml-3 flex-1">
                            <p className="text-sm font-medium">{flash.error}</p>
                        </div>
                        <button onClick={() => setShow(false)} className="ml-4 inline-flex text-red-500 hover:text-red-600">
                            <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    {flash.recovery_suggestion && (
                        <div className="mt-2 ml-8 rounded border border-red-200 bg-white p-2 text-xs text-red-700 dark:border-red-800 dark:bg-gray-800 dark:text-red-300">
                            <span className="font-bold uppercase tracking-wider">Suggestion:</span> {flash.recovery_suggestion}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
