import { useSyncExternalStore } from 'react';

const MOBILE_BREAKPOINT = 768;
const MAX_WIDTH = MOBILE_BREAKPOINT - 1;

const mediaQuery = window.matchMedia(`(max-width: ${String(MAX_WIDTH)}px)`);

function mediaQueryListener(callback: (event: MediaQueryListEvent) => void) {
    mediaQuery.addEventListener('change', callback);

    return () => {
        mediaQuery.removeEventListener('change', callback);
    };
}

function isSmallerThanBreakpoint() {
    return mediaQuery.matches;
}

export function useIsMobile() {
    return useSyncExternalStore(mediaQueryListener, isSmallerThanBreakpoint);
}
