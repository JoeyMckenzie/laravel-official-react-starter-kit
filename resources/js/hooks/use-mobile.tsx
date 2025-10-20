import { useSyncExternalStore } from 'react';

const MOBILE_BREAKPOINT = 768;
const MAX_WIDTH = MOBILE_BREAKPOINT - 1;

const mql = window.matchMedia(`(max-width: ${String(MAX_WIDTH)}px)`);

function mediaQueryListener(callback: (event: MediaQueryListEvent) => void) {
    mql.addEventListener('change', callback);

    return () => {
        mql.removeEventListener('change', callback);
    };
}

function isSmallerThanBreakpoint() {
    return mql.matches;
}

export function useIsMobile() {
    return useSyncExternalStore(mediaQueryListener, isSmallerThanBreakpoint);
}
