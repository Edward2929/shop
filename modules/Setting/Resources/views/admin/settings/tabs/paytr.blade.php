<div class="row">
    <div class="col-md-8">
        {{ Form::checkbox('paytr_enabled', trans('setting::attributes.paytr_enabled'), trans('setting::settings.tabs.enable_paytr'), $errors, $settings) }}
        {{ Form::text('translatable[paytr_label]', trans('setting::attributes.paytr_label'), $errors, $settings, ['required' => true]) }}
        {{ Form::textarea('translatable[paytr_description]', trans('setting::attributes.paytr_description'), $errors, $settings, ['rows' => 3, 'required' => true]) }}
        {{ Form::checkbox('paytr_test_mode', trans('setting::attributes.paytr_test_mode'), trans('setting::settings.form.use_sandbox_for_test_payments'), $errors, $settings) }}

        <div class="{{ old('paytr_enabled', array_get($settings, 'paytr_enabled')) ? '' : 'hide' }}" id="paytr-fields">
            {{ Form::select('paytr_mode', trans('setting::attributes.paytr_mode'), $errors, $modes, $settings, ['required' => true]) }}
            {{ Form::text('paytr_merchant_id', trans('setting::attributes.paytr_merchant_id'), $errors, $settings, ['required' => true]) }}
            {{ Form::password('paytr_merchant_key', trans('setting::attributes.paytr_merchant_key'), $errors, $settings, ['required' => true]) }}
            {{ Form::password('paytr_merchant_salt', trans('setting::attributes.paytr_merchant_salt'), $errors, $settings, ['required' => true]) }}
            {{ Form::checkbox('paytr_installment_enabled', trans('setting::attributes.paytr_installment_enabled'), trans('setting::settings.tabs.enable_paytr_installment'), $errors, $settings) }}
            {{ Form::text('paytr_max_installment', trans('setting::attributes.paytr_max_installment'), $errors, $settings, ['type' => 'number', 'min' => 0, 'max' => 12, 'placeholder' => '0']) }}
            {{ Form::checkbox('paytr_installment_commission_to_customer', trans('setting::attributes.paytr_installment_commission_to_customer'), trans('setting::settings.tabs.paytr_commission_to_customer'), $errors, $settings) }}
            <div class="{{ old('paytr_installment_commission_to_customer', array_get($settings, 'paytr_installment_commission_to_customer')) ? '' : 'hide' }}" id="paytr-commission-fields">
                {{ Form::text('paytr_installment_commission_rate', trans('setting::attributes.paytr_installment_commission_rate'), $errors, $settings, ['type' => 'number', 'step' => '0.01', 'min' => 0, 'max' => 100, 'placeholder' => '0.00']) }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const toggle = (checkbox, targetId) => {
            const el = document.getElementById(targetId);
            if (!el) return;
            checkbox.addEventListener('change', () => {
                el.classList.toggle('hide', !checkbox.checked);
            });
        };

        const enabledCb = document.querySelector('[name="paytr_enabled"]');
        if (enabledCb) toggle(enabledCb, 'paytr-fields');

        const commCb = document.querySelector('[name="paytr_installment_commission_to_customer"]');
        if (commCb) toggle(commCb, 'paytr-commission-fields');
    })();
</script>
@endpush
