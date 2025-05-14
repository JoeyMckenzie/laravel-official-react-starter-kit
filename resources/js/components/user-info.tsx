import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

export function UserInfo({
    user,
    showEmail = false,
}: { user: App.Data.UserData; showEmail?: boolean }) {
    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage
                    src={user.profileImage ?? undefined}
                    alt={user.fullName}
                />
                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                    {user.initials}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.fullName}</span>
                {showEmail && (
                    <span className="truncate text-muted-foreground text-xs">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
