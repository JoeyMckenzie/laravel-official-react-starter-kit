declare namespace App.Data {
    export type UserData = {
        id: number;
        firstName: string;
        lastName: string;
        fullName: string;
        initials: string;
        email: string;
        profileImage: string | null;
        emailVerifiedAt: string | null;
        createdAt: string;
        updatedAt: string;
    };
}
