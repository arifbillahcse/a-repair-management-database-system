/**
 * components/forms.js — port of public/js/form-validation.js
 * Collects form values, paints field errors, and provides the client
 * autocomplete that used to hit GET /api/customers/autocomplete.
 */
'use strict';

const Forms = {

    /** Read every named control into a plain object. */
    collect(form) {
        const data = {};
        new FormData(form).forEach((value, key) => {
            data[key] = typeof value === 'string' ? value.trim() : value;
        });
        // Unchecked checkboxes never appear in FormData — normalise them.
        form.querySelectorAll('input[type="checkbox"][name]').forEach(cb => {
            data[cb.name] = cb.checked ? (cb.value || '1') : '';
        });
        return data;
    },

    clearErrors(form) {
        form.querySelectorAll('.field-error').forEach(el => { el.textContent = ''; });
        form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    },

    /** errors = { field: 'message' } — matches the model validate() output. */
    showErrors(errors) {
        let first = null;
        Object.entries(errors).forEach(([field, msg]) => {
            const err   = document.getElementById(`err-${field}`);
            const input = document.getElementById(field);
            if (err)   err.textContent = msg;
            if (input) { input.classList.add('input-error'); first ??= input; }
        });
        first?.focus();
        first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    /**
     * Client autocomplete for the repair + invoice forms.
     * Writes the chosen id into a hidden input and shows the name.
     */
    customerAutocomplete({ input, hidden, dropdown, onPick = null, preset = null }) {
        const $in   = document.getElementById(input);
        const $id   = document.getElementById(hidden);
        const $list = document.getElementById(dropdown);
        if (!$in || !$id || !$list) return;

        if (preset) { $in.value = preset.full_name; $id.value = preset.customer_id; }

        let active = -1, rows = [];

        const close = () => { $list.setAttribute('hidden', ''); $list.innerHTML = ''; active = -1; };

        const choose = row => {
            $in.value = row.full_name;
            $id.value = row.customer_id;
            $in.classList.remove('input-error');
            document.getElementById('err-customer_id') && (document.getElementById('err-customer_id').textContent = '');
            close();
            onPick?.(row);
        };

        const render = () => {
            if (!rows.length) {
                $list.innerHTML = `<div class="ac-empty">No client matches that.
                    <a href="#/customers/create">Add a new client</a></div>`;
            } else {
                $list.innerHTML = rows.map((r, i) => `
                    <div class="ac-item ${i === active ? 'active' : ''}" data-i="${i}">
                        <span class="ac-name">${Utils.e(r.full_name)}</span>
                        <span class="ac-meta">${Utils.e(r.phone)}${r.city ? ' · ' + Utils.e(r.city) : ''}</span>
                    </div>`).join('') +
                    '<div class="ac-footer">↑ ↓ to move · Enter to select</div>';
                $list.querySelectorAll('.ac-item').forEach(el => {
                    el.onmousedown = e => { e.preventDefault(); choose(rows[Utils.intVal(el.dataset.i)]); };
                });
            }
            $list.removeAttribute('hidden');
        };

        $in.oninput = UI.debounce(() => {
            $id.value = '';                       // typing invalidates the previous pick
            const q = $in.value.trim();
            if (q.length < 2) return close();
            rows = Customer.autocomplete(q, 8);
            active = -1;
            render();
        }, 180);

        $in.onkeydown = e => {
            if ($list.hasAttribute('hidden')) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, rows.length - 1); render(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
            else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(rows[active]); }
            else if (e.key === 'Escape') close();
        };

        $in.onblur = () => setTimeout(close, 150);
    },

    /** Live-validate one field on blur using a model validator. */
    liveValidate(form, validator) {
        form.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => {
            el.onblur = () => {
                const { errors } = validator(this.collect(form));
                const err = document.getElementById(`err-${el.name}`);
                if (!err) return;
                if (errors[el.name]) { err.textContent = errors[el.name]; el.classList.add('input-error'); }
                else                 { err.textContent = '';              el.classList.remove('input-error'); }
            };
        });
    },
};
