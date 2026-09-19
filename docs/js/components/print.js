/**
 * components/print.js — port of views/repairs/print.php + views/invoices/print.php
 * Renders a bare, print-styled document with no app chrome around it.
 */
'use strict';

const Print = {

    _company() {
        return {
            name:    DB.setting('company_name', APP_NAME),
            address: DB.setting('company_address', ''),
            phone:   DB.setting('company_phone', ''),
            email:   DB.setting('company_email', ''),
            vat:     DB.setting('company_vat', ''),
            website: DB.setting('company_website', ''),
        };
    },

    _header(docTitle, docRef) {
        const co = this._company();
        return `
            <header class="pr-header">
                <div class="pr-company">
                    <h1>${Utils.e(co.name)}</h1>
                    <p>${Utils.e(co.address)}</p>
                    <p>${Utils.e(co.phone)}${co.email ? ' · ' + Utils.e(co.email) : ''}</p>
                    ${co.vat ? `<p>BIN: ${Utils.e(co.vat)}</p>` : ''}
                </div>
                <div class="pr-doc">
                    <h2>${Utils.e(docTitle)}</h2>
                    <p class="pr-ref">${Utils.e(docRef)}</p>
                    <p class="pr-date">${Utils.formatDate(new Date())}</p>
                </div>
            </header>`;
    },

    _footer(note) {
        const co = this._company();
        return `
            <footer class="pr-footer">
                <p>${Utils.e(note)}</p>
                <p class="pr-thanks">Thank you for your business — ${Utils.e(co.name)}${co.website ? ' · ' + Utils.e(co.website) : ''}</p>
            </footer>`;
    },

    _toolbar(backHref) {
        return `
            <div class="pr-toolbar no-print">
                <a href="${backHref}" class="btn btn-secondary">${Icon.back('')} Back</a>
                <button class="btn btn-primary" id="printNow">${Icon.print('')} Print / Save as PDF</button>
            </div>`;
    },

    /* ── Repair job sheet ─────────────────────────────────────────────────── */

    jobSheet(r) {
        return `
            ${this._toolbar(`#/repairs/${r.repair_id}`)}
            <article class="pr-page">
                ${this._header(L.jobSheet, `${L.jobOne} #${r.repair_id}`)}

                <section class="pr-two-col">
                    <div>
                        <h3 class="pr-section-title">${Utils.e(L.clientOne)}</h3>
                        <p class="pr-strong">${Utils.e(r.customer_name)}</p>
                        <p>${Utils.e(r.customer_phone ?? '')}</p>
                        <p>${Utils.e(r.customer_email ?? '')}</p>
                        <p>${Utils.e(r.customer_city ?? '')}</p>
                    </div>
                    <div>
                        <h3 class="pr-section-title">${Utils.e(L.jobOne)}</h3>
                        <p><span class="pr-label">Received:</span> ${Utils.formatDateTime(r.date_in)}</p>
                        <p><span class="pr-label">Expected:</span> ${r.collection_date ? Utils.formatDate(r.collection_date) : '—'}</p>
                        <p><span class="pr-label">${Utils.e(L.staffOne)}:</span> ${Utils.e(r.technician_name ?? 'Unassigned')}</p>
                        <p><span class="pr-label">Status:</span> ${Utils.e(REPAIR_STATUS[r.status])}</p>
                    </div>
                </section>

                <section>
                    <h3 class="pr-section-title">${Utils.e(L.itemLabel)}</h3>
                    <table class="pr-table">
                        <tbody>
                            <tr><th>${Utils.e(L.itemField)}</th><td>${Utils.e(r.device_model)}</td></tr>
                            <tr><th>${Utils.e(L.serialLabel)}</th><td>${Utils.e(r.device_serial_number ?? '—')}</td></tr>
                            <tr><th>Tracking code</th><td>${Utils.e(r.qr_code ?? '—')}</td></tr>
                        </tbody>
                    </table>
                </section>

                <section>
                    <h3 class="pr-section-title">${Utils.e(L.problemLabel)}</h3>
                    <p class="pr-para">${Utils.e(r.problem_description ?? '—')}</p>

                    <h3 class="pr-section-title">${Utils.e(L.diagnosisLabel)}</h3>
                    <p class="pr-para">${Utils.e(r.diagnosis ?? '—')}</p>

                    <h3 class="pr-section-title">${Utils.e(L.workLabel)}</h3>
                    <p class="pr-para">${Utils.e(r.work_done ?? '—')}</p>
                </section>

                <section class="pr-totals">
                    <table class="pr-table pr-table-right">
                        <tbody>
                            <tr><th>${Utils.e(L.estimateLabel)}</th><td>${r.estimate_amount ? Utils.formatCurrency(r.estimate_amount) : '—'}</td></tr>
                            <tr class="pr-grand"><th>Amount charged</th><td>${r.actual_amount ? Utils.formatCurrency(r.actual_amount) : '—'}</td></tr>
                        </tbody>
                    </table>
                </section>

                <section class="pr-signatures">
                    <div><span class="pr-sig-line"></span><p>${Utils.e(L.staffOne)} signature</p></div>
                    <div><span class="pr-sig-line"></span><p>Customer signature on collection</p></div>
                </section>

                ${this._footer(DB.setting('job_sheet_terms', 'Warranty on replaced parts: 90 days.'))}
            </article>`;
    },

    /* ── Invoice ──────────────────────────────────────────────────────────── */

    invoice(inv, items) {
        return `
            ${this._toolbar(`#/invoices/${inv.invoice_id}`)}
            <article class="pr-page">
                ${this._header('Invoice', inv.invoice_number)}

                <section class="pr-two-col">
                    <div>
                        <h3 class="pr-section-title">Billed to</h3>
                        <p class="pr-strong">${Utils.e(inv.customer_name)}</p>
                        <p>${Utils.e(inv.customer_address ?? '')}</p>
                        <p>${Utils.e([inv.customer_city, inv.customer_postal].filter(Boolean).join(' '))}</p>
                        <p>${Utils.e(inv.customer_phone ?? '')}</p>
                        ${inv.customer_vat ? `<p>BIN: ${Utils.e(inv.customer_vat)}</p>` : ''}
                    </div>
                    <div>
                        <h3 class="pr-section-title">Details</h3>
                        <p><span class="pr-label">Invoice date:</span> ${Utils.formatDate(inv.invoice_date)}</p>
                        <p><span class="pr-label">Due date:</span> ${inv.due_date ? Utils.formatDate(inv.due_date) : '—'}</p>
                        ${inv.repair_id ? `<p><span class="pr-label">${Utils.e(L.jobOne)}:</span> #${inv.repair_id}</p>` : ''}
                        <p><span class="pr-label">Status:</span> ${Utils.e(INVOICE_STATUS[inv.status])}</p>
                    </div>
                </section>

                <table class="pr-table pr-items">
                    <thead>
                        <tr>
                            <th>Description</th><th class="ta-right">Qty</th>
                            <th class="ta-right">Unit price</th><th class="ta-right">Disc.</th>
                            <th class="ta-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(it => `
                            <tr>
                                <td>${Utils.e(it.description)}</td>
                                <td class="ta-right">${Utils.numberFormat(it.quantity, it.quantity % 1 ? 2 : 0)}</td>
                                <td class="ta-right">${Utils.formatCurrency(it.unit_price)}</td>
                                <td class="ta-right">${it.discount_pct ? it.discount_pct + '%' : '—'}</td>
                                <td class="ta-right">${Utils.formatCurrency(it.line_total)}</td>
                            </tr>`).join('')}
                    </tbody>
                </table>

                <section class="pr-totals">
                    <table class="pr-table pr-table-right">
                        <tbody>
                            <tr><th>Subtotal</th><td>${Utils.formatCurrency(inv.subtotal)}</td></tr>
                            <tr><th>VAT (${Utils.numberFormat(inv.tax_percentage, 0)}%)</th><td>${Utils.formatCurrency(inv.tax_amount)}</td></tr>
                            <tr class="pr-grand"><th>Total</th><td>${Utils.formatCurrency(inv.total_amount)}</td></tr>
                            <tr><th>Paid</th><td>${Utils.formatCurrency(inv.amount_paid)}</td></tr>
                            <tr class="pr-due"><th>Balance due</th><td>${Utils.formatCurrency(inv.balance)}</td></tr>
                        </tbody>
                    </table>
                </section>

                ${inv.notes ? `<section><h3 class="pr-section-title">Notes</h3><p class="pr-para">${Utils.e(inv.notes)}</p></section>` : ''}

                ${this._footer(DB.setting('invoice_terms', 'Payment due within 15 days.'))}
            </article>`;
    },

    bind(backHref) {
        document.getElementById('printNow').onclick = () => window.print();
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') { document.removeEventListener('keydown', esc); location.hash = backHref; }
        });
    },
};
