<div class="payment-method">
    <h4 class="title">{{ trans('storefront::checkout.payment_method') }}</h4>

    <div class="payment-method-form">
        <div class="form-group">
            <template x-for="(gateway, name) in gateways">
                <div class="form-radio">
                    <input
                        type="radio"
                        name="payment_method"
                        :value="name"
                        :id="name"
                        x-model="form.payment_method"
                    >

                    <label :for="name" x-text="gateway.label"></label>

                    <span class="helper-text" x-text="gateway.description"></span>
                </div>
            </template>

            <template x-if="hasNoPaymentMethod">
                <span class="error-message">
                    {{ trans('storefront::checkout.no_payment_method') }}
                </span>
            </template>
        </div>
    </div>
</div>

@if (setting('stripe_enabled') && setting('stripe_integration_type') === 'embedded_form')
    <div x-cloak id="stripe-element" x-show="form.payment_method === 'stripe'">
        {{-- A Stripe Element will be mounted here dynamically. --}}
    </div>
@endif

<template x-if="shouldShowPaymentInstructions">
    <div class="payment-instructions">
        <h4 class="title">{{ trans('storefront::checkout.payment_instructions') }}</h4>

        <p x-html="paymentInstructions"></p>
    </div>
</template>

@if (setting('paytr_enabled') && setting('paytr_mode') === 'direct')
    <template x-if="form.payment_method === 'paytr'">
        <div class="paytr-direct-form" style="margin-top:16px;">
            <h4 class="title">{{ trans('storefront::checkout.card_information') }}</h4>

            <div class="form-group">
                <label>{{ trans('storefront::checkout.card_holder') }}</label>
                <input type="text" class="form-control" x-model="form.paytr_cc_owner"
                    placeholder="{{ trans('storefront::checkout.card_holder') }}" autocomplete="cc-name" />
            </div>

            <div class="form-group">
                <label>{{ trans('storefront::checkout.card_number') }}</label>
                <input type="text" class="form-control" x-model="paytrCardNumber"
                    @input="form.paytr_card_number = $event.target.value; paytrFetchInstallments($event.target.value)"
                    placeholder="•••• •••• •••• ••••" maxlength="19" autocomplete="cc-number" />
            </div>

            <div style="display:flex;gap:12px;">
                <div class="form-group" style="flex:1;">
                    <label>{{ trans('storefront::checkout.expiry_month') }}</label>
                    <input type="text" class="form-control" x-model="form.paytr_expiry_month"
                        placeholder="MM" maxlength="2" autocomplete="cc-exp-month" />
                </div>
                <div class="form-group" style="flex:1;">
                    <label>{{ trans('storefront::checkout.expiry_year') }}</label>
                    <input type="text" class="form-control" x-model="form.paytr_expiry_year"
                        placeholder="YY" maxlength="2" autocomplete="cc-exp-year" />
                </div>
                <div class="form-group" style="flex:1;">
                    <label>{{ trans('storefront::checkout.cvv') }}</label>
                    <input type="password" class="form-control" x-model="form.paytr_cvv"
                        placeholder="•••" maxlength="4" autocomplete="cc-csc" />
                </div>
            </div>

            @if (setting('paytr_installment_enabled'))
                <template x-if="paytrInstallments.length > 0">
                    <div class="paytr-installments" style="margin-top:12px;">
                        <label>{{ trans('storefront::checkout.select_installment') }}</label>
                        <div class="paytr-installment-list" style="margin-top:8px;">
                            <template x-for="inst in paytrInstallments" :key="inst.count">
                                <div class="form-radio">
                                    <input type="radio" :id="'inst_' + inst.count"
                                        :value="inst.count"
                                        x-model.number="paytrInstallmentCount"
                                        @change="selectPaytrInstallment(inst.count)" />
                                    <label :for="'inst_' + inst.count">
                                        <span x-text="inst.label"></span>
                                        — <span x-text="inst.monthly"></span>
                                        <template x-if="inst.count > 1">
                                            <span> ({{ trans('storefront::checkout.paytr_total') }}: <span x-text="inst.totalAmount"></span>)</span>
                                        </template>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            @endif
        </div>
    </template>
@endif
