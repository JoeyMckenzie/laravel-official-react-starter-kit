import { toUrl } from '@/lib/utils';
import { type InertiaLinkProps, usePage } from '@inertiajs/react';

export function useActiveUrl() {
    const page = usePage();
    const currentUrlPath = new URL(page.url, window.location.origin).pathname;

    function urlIsActive(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        const urlToCompare = currentUrl ?? currentUrlPath;
        return toUrl(urlToCheck) === urlToCompare;
    }

    return {
        currentUrl: currentUrlPath,
        urlIsActive,
    };
}
