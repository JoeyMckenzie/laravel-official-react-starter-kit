import { useEffect, useState } from 'react';

const MOBILE_BREAKPOINT = 768;

export function useIsMobile() {
    const [isMobile, setIsMobile] = useState<boolean>();

    useEffect(() => {
        const breakpoint = MOBILE_BREAKPOINT - 1;
        const mql = window.matchMedia(`(max-width: ${breakpoint.toString()}px)`);

        const onChange = () => {
            setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
        };

        mql.addEventListener('change', onChange);
        setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);

        return () => {
            mql.removeEventListener('change', onChange);
        };
    }, []);

    return Boolean(isMobile);
}
