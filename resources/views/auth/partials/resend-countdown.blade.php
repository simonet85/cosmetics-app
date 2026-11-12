{{--
    Greys out "Renvoyer le code" while the server would refuse it, and counts the seconds down. The server stays the
    judge (without JavaScript the button works and the refusal comes back as a message); this only saves the wasted tap.
    Expects $retryIn (seconds still to wait) and the button id "resend-button".
--}}
@if ($retryIn > 0)
    <script>
        (function () {
            var button = document.getElementById('resend-button');
            if (!button) return;
            var label = button.textContent.trim();
            var template = @json(__('Renvoyer le code dans :seconds s'));
            var remaining = {{ (int) $retryIn }};

            function tick() {
                if (remaining <= 0) {
                    button.disabled = false;
                    button.textContent = label;
                    return;
                }
                button.disabled = true;
                button.textContent = template.replace(':seconds', remaining);
                remaining -= 1;
                setTimeout(tick, 1000);
            }

            tick();
        })();
    </script>
@endif
