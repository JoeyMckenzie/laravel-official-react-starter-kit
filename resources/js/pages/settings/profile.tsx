import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import ProfileImageController from '@/actions/App/Http/Controllers/Settings/ProfileImageController';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { Camera, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const [isUploadingImage, setIsUploadingImage] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleImageCallback = () => {
        setIsUploadingImage(false);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setIsUploadingImage(true);
        const formData = new FormData();
        formData.append('image', file);

        router.post(ProfileImageController.store().url, formData, {
            onSuccess: handleImageCallback,
            onError: () => handleImageCallback,
        });
    };

    const handleImageDelete = () => {
        router.delete(ProfileImageController.destroy().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Profile information" description="Update your name and email address" />

                    {/* Profile Image Section */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-6">
                            <Avatar className="h-20 w-20">
                                <AvatarImage src={auth.user?.profile_image} alt={auth.user?.full_name} />
                                <AvatarFallback className="bg-neutral-200 text-xl text-black dark:bg-neutral-700 dark:text-white">
                                    {auth.user?.initials}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => fileInputRef.current?.click()}
                                    disabled={isUploadingImage}
                                    className="flex items-center gap-2"
                                >
                                    <Camera className="h-4 w-4" />
                                    {isUploadingImage ? 'Uploading...' : 'Change Photo'}
                                </Button>

                                {auth.user?.avatar ? (
                                    <Button type="button" variant="outline" onClick={handleImageDelete} className="flex items-center gap-2">
                                        <Trash2 className="h-4 w-4" />
                                        Remove
                                    </Button>
                                ) : null}
                            </div>
                        </div>

                        <input ref={fileInputRef} type="file" accept="image/*" onChange={handleImageUpload} className="hidden" />

                        <p className="text-sm text-muted-foreground">
                            Upload a square image for best results. Maximum size: 5MB. Minimum dimensions: 100x100px.
                        </p>
                    </div>
                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="first_name">First name</Label>

                                        <Input
                                            id="first_name"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user?.first_name}
                                            name="first_name"
                                            required
                                            autoComplete="first_name"
                                            placeholder="First name"
                                        />

                                        <InputError className="mt-2" message={errors.first_name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="last_name">Last name</Label>

                                        <Input
                                            id="last_name"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user?.last_name}
                                            name="last_name"
                                            required
                                            autoComplete="last_name"
                                            placeholder="Last name"
                                        />

                                        <InputError className="mt-2" message={errors.last_name} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user?.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError className="mt-2" message={errors.email} />
                                </div>

                                {mustVerifyEmail && auth.user?.email_verified_at === null ? (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to resend the verification email.
                                            </Link>
                                        </p>

                                        {status === 'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                ) : null}

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing} data-test="update-profile-button">
                                        Save
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">Saved</p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
