/**
 * form-validation.js — Client-side form validation helpers
 *
 * Usage in views:
 *   <form data-validate>
 *     <input name="email" data-rules="required|email">
 *     <input name="phone" data-rules="required|phone">
 *     <div class="field-error" data-error-for="email"></div>
 *   </form>
 */
(function () {
    'use strict';

    // ── Validators ────────────────────────────────────────────────────────────
    var VALIDATORS = {
        required: function (val) {
            return val.trim() !== '' ? null : 'This field is required.';
        },
        email: function (val) {
            if (!val) return null; // handled by required
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)
                ? null
                : 'Please enter a valid email address.';
        },
        phone: function (val) {
            if (!val) return null;
            var clean = val.replace(/[\s\-().+]/g, '');
            return /^\d{7,15}$/.test(clean) ? null : 'Please enter a valid phone number.';
        },
        minlength: function (val, param) {
            return val.length >= parseInt(param, 10)
                ? null
                : 'Must be at least ' + param + ' characters.';
        },
        maxlength: function (val, param) {
            return val.length <= parseInt(param, 10)
                ? null
                : 'Must be no more than ' + param + ' characters.';
        },
        numeric: function (val) {
            if (!val) return null;
            return /^\d+(\.\d+)?$/.test(val) ? null : 'Must be a numeric value.';
        },
        match: function (val, param) {
            var other = document.querySelector('[name="' + param + '"]');
            return other && val === other.value ? null : 'Fields do not match.';
        },
    };

    // ── Validate a single field ───────────────────────────────────────────────
    function validateField(input) {
        var rules = (input.dataset.rules || '').split('|').filter(Boolean);
        var val   = input.value;

        for (var i = 0; i < rules.length; i++) {
            var parts  = rules[i].split(':');
            var name   = parts[0];
            var param  = parts[1];
            var fn     = VALIDATORS[name];

            if (!fn) continue;
            var error = fn(val, param);
            if (error) return error;
        }
        return null;
    }

    // ── Apply error to a field ────────────────────────────────────────────────
    function setFieldError(input, message) {
        input.classList.toggle('input-error', !!message);

        // Find the matching error container (data-error-for="fieldName")
        var name = input.name || input.id;
        var errEl = document.querySelector('[data-error-for="' + name + '"]');
        if (errEl) errEl.textContent = message || '';
    }

    // ── Validate entire form ─────────────────────────────────────────────────
    function validateForm(form) {
        var inputs = form.querySelectorAll('[data-rules]');
        var valid  = true;

        inputs.forEach(function (input) {
            var error = validateField(input);
            setFieldError(input, error);
            if (error) valid = false;
        });

        return valid;
    }

    // ── Wire up forms with [data-validate] ────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form[data-validate]');

        forms.forEach(function (form) {
            // Real-time validation on blur
            form.querySelectorAll('[data-rules]').forEach(function (input) {
                input.addEventListener('blur', function () {
                    setFieldError(input, validateField(input));
                });
                input.addEventListener('input', function () {
                    // Clear error once user starts typing
                    if (input.classList.contains('input-error')) {
                        setFieldError(input, validateField(input));
                    }
                });
            });

            // Submit validation
            form.addEventListener('submit', function (e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                    // Focus first invalid field
                    var firstErr = form.querySelector('.input-error');
                    if (firstErr) firstErr.focus();
                }
            });
        });
    });

    // ── Auto-combine first + last name → full_name ───────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        var first = document.getElementById('first_name');
        var last  = document.getElementById('last_name');
        var full  = document.getElementById('full_name');

        if (!first || !last || !full) return;

        function updateFullName() {
            // Only auto-fill if full_name is empty or was previously auto-filled
            if (!full.dataset.manuallyEdited) {
                full.value = (first.value.trim() + ' ' + last.value.trim()).trim();
            }
        }

        first.addEventListener('input', updateFullName);
        last.addEventListener('input', updateFullName);

        // If user manually edits full_name, stop auto-combining
        full.addEventListener('input', function () {
            full.dataset.manuallyEdited = '1';
        });
    });

    // ── Character counter for textareas ──────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('textarea[maxlength]').forEach(function (ta) {
            var max     = parseInt(ta.getAttribute('maxlength'), 10);
            var counter = document.createElement('small');
            counter.className = 'char-counter';
            counter.style.cssText = 'display:block;text-align:right;color:var(--text-muted);font-size:.72rem;margin-top:.2rem;';
            ta.parentNode.insertBefore(counter, ta.nextSibling);

            function update() {
                var left = max - ta.value.length;
                counter.textContent = left + ' characters remaining';
                counter.style.color = left < 20 ? 'var(--warning)' : 'var(--text-muted)';
            }
            ta.addEventListener('input', update);
            update();
        });
    });

    // ── Price formatter (formats as decimal on blur) ──────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[data-type="currency"]').forEach(function (inp) {
            inp.addEventListener('blur', function () {
                var v = parseFloat(inp.value.replace(',', '.'));
                if (!isNaN(v)) inp.value = v.toFixed(2);
            });
        });
    });

})();
