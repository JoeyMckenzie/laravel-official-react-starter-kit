import { useSyncExternalStore } from 'react';

const MOBILE_BREAKPOINT = 768;
const MAX_WIDTH = MOBILE_BREAKPOINT - 1;

const mql =
    typeof window === 'undefined'
        ? undefined
        : window.matchMedia(`(max-width: ${String(MAX_WIDTH)}px)`);

function mediaQueryListener(callback: (event: MediaQueryListEvent) => void) {
    if (!mql) {
        // eslint-disable-next-line @typescript-eslint/no-empty-function
        return () => {};
    }

    mql.addEventListener('change', callback);

    return () => {
        mql.removeEventListener('change', callback);
    };
}

function isSmallerThanBreakpoint(): boolean {
    return mql?.matches ?? false;
}

function getServerSnapshot(): boolean {
    return false;
}

export function useIsMobile(): boolean {
    return useSyncExternalStore(
        mediaQueryListener,
        isSmallerThanBreakpoint,
        getServerSnapshot,
    );
}
