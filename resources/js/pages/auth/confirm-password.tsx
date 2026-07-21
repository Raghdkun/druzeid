import { Form, Head } from '@inertiajs/react';

import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="تأكيد كلمة المرور" />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label="التأكيد بمفتاح المرور"
                loadingLabel="جارٍ التأكيد…"
                separator="أو أكّد بكلمة المرور"
            />

            <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-5">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-1.5">
                            <Label htmlFor="password">كلمة المرور</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                dir="ltr"
                                placeholder="••••••••"
                                autoComplete="current-password"
                                autoFocus
                            />
                            <InputError message={errors.password} />
                        </div>

                        <Button
                            size="lg"
                            className="w-full"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing && <Spinner />}
                            تأكيد
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'تأكيد كلمة المرور',
    description: 'هذه منطقة محمية. يرجى تأكيد كلمة المرور قبل المتابعة.',
};
