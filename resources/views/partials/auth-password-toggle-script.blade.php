@once('auth-password-toggle-script')
    @push('scripts')
        <script>
            (function () {
                function togglePasswordVisibility(btn) {
                    var id = btn.getAttribute('data-password-toggle');
                    var input = document.getElementById(id);

                    if (!input) {
                        return;
                    }

                    input.type = input.type === 'password' ? 'text' : 'password';

                    var mode = input.type === 'password' ? 'password' : 'text';

                    btn.querySelectorAll('[data-pass-icon]').forEach(function (svg) {
                        svg.classList.toggle('hidden', svg.getAttribute('data-pass-icon') !== mode);
                    });
                }

                function bindPasswordToggles() {
                    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
                        if (btn.dataset.passwordToggleBound === '1') {
                            return;
                        }

                        btn.dataset.passwordToggleBound = '1';
                        btn.addEventListener('click', function () {
                            togglePasswordVisibility(btn);
                        });
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bindPasswordToggles);
                } else {
                    bindPasswordToggles();
                }
            })();
        </script>
    @endpush
@endonce
