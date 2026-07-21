<script>
(function () {
    var STORAGE_KEY = 'esewaan.appearance';
    var DEFAULTS = {
        theme: 'light',
        fontSize: 'md',
        contrast: 'normal',
    };

    function readPrefs() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (! raw) {
                return Object.assign({}, DEFAULTS);
            }

            return Object.assign({}, DEFAULTS, JSON.parse(raw));
        } catch (e) {
            return Object.assign({}, DEFAULTS);
        }
    }

    function resolvedTheme(theme) {
        if (theme === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        return theme === 'dark' ? 'dark' : 'light';
    }

    function applyPrefs(prefs) {
        var root = document.documentElement;
        var theme = resolvedTheme(prefs.theme);

        root.setAttribute('data-theme', theme);
        root.setAttribute('data-font-size', prefs.fontSize || 'md');
        root.setAttribute('data-contrast', prefs.contrast || 'normal');
        root.removeAttribute('data-density');
        root.removeAttribute('data-reduce-motion');
        root.removeAttribute('data-underline-links');
        root.style.colorScheme = theme;
    }

    window.EsewaanAppearance = {
        STORAGE_KEY: STORAGE_KEY,
        DEFAULTS: DEFAULTS,
        read: readPrefs,
        apply: applyPrefs,
        save: function (prefs) {
            var next = Object.assign({}, DEFAULTS, prefs);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
            applyPrefs(next);
            return next;
        },
        reset: function () {
            localStorage.removeItem(STORAGE_KEY);
            applyPrefs(DEFAULTS);
            return Object.assign({}, DEFAULTS);
        },
    };

    applyPrefs(readPrefs());

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
        var prefs = readPrefs();
        if (prefs.theme === 'system') {
            applyPrefs(prefs);
        }
    });
})();
</script>
