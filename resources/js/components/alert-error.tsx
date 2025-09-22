import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircleIcon } from 'lucide-react';

export default function AlertError({
    errors,
    title,
}: {
    errors: string[];
    title?: string;
}) {
    const uniqueErrors = [...new Set(errors)];

    return (
        <Alert variant="destructive">
            <AlertCircleIcon />
            <AlertTitle>{title ?? 'Something went wrong.'}</AlertTitle>
            <AlertDescription>
                <ul className="list-inside list-disc text-sm">
                    {uniqueErrors.map((error) => (
                        <li key={error}>{error}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}
