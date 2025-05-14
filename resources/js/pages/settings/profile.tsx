import DeleteUser from "@/components/delete-user";
import HeadingSmall from "@/components/heading-small";
import InputError from "@/components/input-error";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import SettingsLayout from "@/layouts/settings/layout";
import type { BreadcrumbItem, SharedData } from "@/types";
import { Transition } from "@headlessui/react";
import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import { type FormEventHandler, useRef, useState } from "react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Profile settings",
        href: "/settings/profile",
    },
];

type ProfileForm = {
    _method: string;
    first_name: string;
    last_name: string;
    email: string;
    profile_image?: File | null;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const [profileImage, setProfileImage] = useState<string | null>(null);
    const photoInput = useRef<HTMLInputElement>(null);
    const { data, setData, post, errors, processing, recentlySuccessful } =
        useForm<Required<ProfileForm>>({
            _method: "patch",
            first_name: auth.user.first_name,
            last_name: auth.user.last_name,
            email: auth.user.email,
            profile_image: null,
        });

    const selectNewPhoto = () => {
        photoInput.current?.click();
    };

    const updatePhotoPreview = () => {
        const photo = photoInput.current?.files?.[0];

        if (!photo) {
            return;
        }

        setData("profile_image", photo);

        const reader = new FileReader();
        reader.onload = (e: ProgressEvent<FileReader>) => {
            setProfileImage(e.target?.result as string);
        };

        reader.readAsDataURL(photo);
    };

    const deletePhoto = () => {
        router.delete(route("profile-photo.destroy"), {
            preserveScroll: true,
            onSuccess: () => {
                setProfileImage(null);
                clearPhotoFileInput();
            },
        });
    };

    const clearPhotoFileInput = () => {
        if (photoInput.current) {
            photoInput.current.value = "";
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("profile.update"), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Profile information"
                        description="Update your name and email address"
                    />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Input
                                type="file"
                                ref={photoInput}
                                id="photo"
                                className="hidden"
                                onChange={updatePhotoPreview}
                                accept="image/*"
                            />

                            <div className="flex items-center gap-4">
                                <Avatar className="h-20 w-20">
                                    <AvatarImage
                                        src={
                                            profileImage ??
                                            auth.user.profile_image ??
                                            undefined
                                        }
                                        alt={auth.user.full_name}
                                    />
                                    <AvatarFallback>
                                        {auth.user.initials}
                                    </AvatarFallback>
                                </Avatar>

                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={selectNewPhoto}
                                >
                                    Select photo
                                </Button>

                                {auth.user.profile_image && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={deletePhoto}
                                    >
                                        Remove photo
                                    </Button>
                                )}
                            </div>

                            <InputError
                                className="mt-2"
                                message={errors.profile_image}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">First name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData("first_name", e.target.value)
                                    }
                                    required
                                    autoComplete="last_name"
                                    placeholder="First name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.first_name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Last name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData("last_name", e.target.value)
                                    }
                                    required
                                    autoComplete="first_name"
                                    placeholder="Last name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.last_name}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                                required
                                autoComplete="username"
                                placeholder="Email address"
                            />

                            <InputError
                                className="mt-2"
                                message={errors.email}
                            />
                        </div>

                        {mustVerifyEmail &&
                            auth.user.email_verified_at === null && (
                                <div>
                                    <p className="-mt-4 text-muted-foreground text-sm">
                                        Your email address is unverified.{" "}
                                        <Link
                                            href={route("verification.send")}
                                            method="post"
                                            as="button"
                                            className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                        >
                                            Click here to resend the
                                            verification email.
                                        </Link>
                                    </p>

                                    {status === "verification-link-sent" && (
                                        <div className="mt-2 font-medium text-green-600 text-sm">
                                            A new verification link has been
                                            sent to your email address.
                                        </div>
                                    )}
                                </div>
                            )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-neutral-600 text-sm">
                                    Saved
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
