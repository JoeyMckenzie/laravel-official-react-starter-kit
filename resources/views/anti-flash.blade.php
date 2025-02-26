<style>
    /* Critical CSS to prevent flash of incorrect theme */
    html:not([data-theme]) body {
        visibility: hidden;
    }

    html[data-theme] body {
        visibility: visible;
        transition: background-color 0.15s ease;
    }

    /* Ensure smooth transition once theme is applied */
    .dark {
        color-scheme: dark;
    }
</style>
