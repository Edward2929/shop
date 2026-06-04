<div id="installments" class="tab-pane installments-tab">
    <div x-data="{ get installmentRows() {
        const price = hasSpecialPrice ? specialPrice : regularPrice;
        const maxInst = {{ (int)(setting('paytr_max_installment') ?: 12) }};
        const commRate = {{ (float)(setting('paytr_installment_commission_rate') ?: 0) }};
        const rows = [];
        for (let i = 2; i <= maxInst; i++) {
            const factor = commRate > 0 ? (1 + commRate / 100) : 1;
            const total = price * factor;
            rows.push({ count: i, monthly: total / i, total: total });
        }
        return rows;
    } }">
        <table class="table paytr-installment-table">
            <thead>
                <tr>
                    <th>{{ trans('storefront::product.installment_count') }}</th>
                    <th>{{ trans('storefront::product.installment_monthly') }}</th>
                    <th>{{ trans('storefront::product.installment_total') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ trans('storefront::product.installment_single') }}</td>
                    <td x-text="formatCurrency(hasSpecialPrice ? specialPrice : regularPrice)"></td>
                    <td x-text="formatCurrency(hasSpecialPrice ? specialPrice : regularPrice)"></td>
                </tr>
                <template x-for="row in installmentRows" :key="row.count">
                    <tr>
                        <td x-text="row.count"></td>
                        <td x-text="formatCurrency(row.monthly)"></td>
                        <td x-text="formatCurrency(row.total)"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
