<script>
(function () {
    var STORAGE_KEY = 'esewaan.sidebar';

    function readState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            var parsed = raw ? JSON.parse(raw) : {};

            return {
                open: parsed.open && typeof parsed.open === 'object' ? parsed.open : {},
                scrollTop: typeof parsed.scrollTop === 'number' ? parsed.scrollTop : 0,
            };
        } catch (e) {
            return { open: {}, scrollTop: 0 };
        }
    }

    function writeState(state) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // Ignore private browsing or storage quota errors.
        }
    }

    var state = readState();

    document.querySelectorAll('[data-sidebar-menu]').forEach(function (menu) {
        var key = menu.getAttribute('data-sidebar-menu');

        if (Object.prototype.hasOwnProperty.call(state.open, key)) {
            menu.open = state.open[key];
        }

        menu.addEventListener('toggle', function (event) {
            if (! event.isTrusted) {
                return;
            }

            var current = readState();
            current.open[key] = menu.open;
            writeState(current);

            document.querySelectorAll('[data-sidebar-menu="' + key + '"]').forEach(function (twin) {
                if (twin !== menu) {
                    twin.open = menu.open;
                }
            });
        });
    });

    document.querySelectorAll('.sidebar-scroll').forEach(function (nav) {
        nav.style.scrollBehavior = 'auto';
        nav.scrollTop = state.scrollTop;
        nav.style.scrollBehavior = '';

        nav.addEventListener('scroll', function () {
            var current = readState();
            current.scrollTop = nav.scrollTop;
            writeState(current);
        }, { passive: true });
    });
})();
</script>
