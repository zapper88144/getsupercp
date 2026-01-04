import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { ShieldCheckIcon, KeyIcon } from '@heroicons/react/24/outline';

export default function Challenge() {
    const [recovery, setRecovery] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    const toggleRecovery = () => {
        setRecovery(!recovery);
        reset('code', 'recovery_code');
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('two-factor.verify'), {
            onFinish: () => reset('code', 'recovery_code'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Two-Factor Authentication" />

            <div className="mb-6 text-center">
                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/20">
                    <ShieldCheckIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Two-Factor Authentication</h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {recovery
                        ? 'Please confirm access to your account by entering one of your emergency recovery codes.'
                        : 'Please confirm access to your account by entering the authentication code provided by your authenticator application.'}
                </p>
            </div>

            <form onSubmit={submit} className="space-y-6">
                {!recovery ? (
                    <div>
                        <InputLabel htmlFor="code" value="Code" />
                        <TextInput
                            id="code"
                            type="text"
                            inputMode="numeric"
                            name="code"
                            value={data.code}
                            className="mt-1 block w-full text-center text-2xl tracking-widest"
                            autoComplete="one-time-code"
                            isFocused={true}
                            onChange={(e) => setData('code', e.target.value)}
                            placeholder="000000"
                            maxLength={6}
                        />
                        <InputError message={errors.code} className="mt-2" />
                    </div>
                ) : (
                    <div>
                        <InputLabel htmlFor="recovery_code" value="Recovery Code" />
                        <TextInput
                            id="recovery_code"
                            type="text"
                            name="recovery_code"
                            value={data.recovery_code}
                            className="mt-1 block w-full font-mono"
                            autoComplete="one-time-code"
                            isFocused={true}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                            placeholder="abcd1-efgh2"
                        />
                        <InputError message={errors.recovery_code} className="mt-2" />
                    </div>
                )}

                <div className="flex flex-col gap-4">
                    <PrimaryButton className="w-full justify-center py-3 text-base" disabled={processing}>
                        Log in
                    </PrimaryButton>

                    <button
                        type="button"
                        onClick={toggleRecovery}
                        className="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        {recovery ? 'Use an authentication code' : 'Use a recovery code'}
                    </button>
                </div>
            </form>
        </GuestLayout>
    );
}
