@extends('storefront::public.layout')

@section('title', trans('storefront::checkout.checkout'))

@section('content')
    <section
        x-data="
            Checkout({
                customerEmail: '{{ auth()->user()->email ?? null }}',
                customerPhone: '{{ auth()->user()->phone ?? null }}',
                addresses: {{ $addresses }},
                defaultAddress: {{ $defaultAddress }},
                gateways: {{ $gateways }},
                countries: {{ json_encode($countries) }}
            })
        "
        class="checkout-wrap"
    >
        <div class="container">
            @include('storefront::public.cart.index.steps')

            <form class="checkout-form" @input="errors.clear($event.target.name)">
                <div class="checkout-inner">
                    <div class="checkout-left">
                        @include('storefront::public.checkout.create.form.account_details')
                        @include('storefront::public.checkout.create.form.billing_details')
                        @include('storefront::public.checkout.create.form.shipping_details')
                        @include('storefront::public.checkout.create.form.order_note')
                    </div>

                    <div class="checkout-right">
                        @include('storefront::public.checkout.create.payment')
                        @include('storefront::public.checkout.create.shipping')
                    </div>
                </div>

                @include('storefront::public.checkout.create.order_summary')
            </form>

            @if (setting('authorizenet_enabled'))
                <template x-if="authorizeNetToken">
                    <form
                        x-ref="authorizeNetForm"
                        method="post"
                        action="{{
                            setting('authorizenet_test_mode') ?
                            'https://test.authorize.net/payment/payment' :
                            'https://accept.authorize.net/payment/payment'
                        }}"
                    >
                        <input type="hidden" name="token" :value="authorizeNetToken" />

                        <button type="submit"></button>
                    </form>
                </template>
            @endif

            @if (setting('payfast_enabled'))
                <form
                    x-ref="payFastForm"
                    method="post"
                    action="https://{{ setting('payfast_test_mode') ? 'sandbox.' : '' }}payfast.co.za/eng/process"
                >
                    <template x-for="(value, name, index) in payFastFormFields" :key="index">
                        <input :name="name" type="hidden" :value="value" />
                    </template>
                </form>
            @endif

            @if (setting('paytr_enabled') && setting('paytr_mode') === 'iframe')
                {{-- PayTR iFrame modal --}}
                <template x-if="paytrIframeToken">
                    <div class="paytr-iframe-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:9999;display:flex;align-items:center;justify-content:center;">
                        <div style="background:#fff;border-radius:8px;padding:16px;width:min(600px,95vw);position:relative;">
                            <button type="button"
                                style="position:absolute;top:8px;right:12px;background:none;border:none;font-size:20px;cursor:pointer;"
                                @click="paytrIframeToken = null; deleteOrder(null)">✕</button>
                            <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                            <iframe
                                :src="'https://www.paytr.com/odeme/guvenli/' + paytrIframeToken"
                                id="paytriframe"
                                frameborder="0"
                                scrolling="no"
                                style="width:100%;"
                            ></iframe>
                            <script>iFrameResize({},'#paytriframe');</script>
                        </div>
                    </div>
                </template>
            @endif

            @if (setting('paytr_enabled') && setting('paytr_mode') === 'direct')
                {{-- PayTR Direct API hidden form (submits card data directly to PayTR) --}}
                <template x-if="paytrDirectFormFields">
                    <form
                        x-ref="paytrDirectForm"
                        method="post"
                        :action="paytrDirectPayUrl"
                    >
                        <template x-for="(value, name) in paytrDirectFormFields" :key="name">
                            <input :name="name" type="hidden" :value="value" />
                        </template>
                        {{-- Card fields come from checkout form --}}
                        <input type="hidden" name="cc_owner" :value="form.paytr_cc_owner" />
                        <input type="hidden" name="card_number" :value="(form.paytr_card_number || '').replace(/\s/g, '')" />
                        <input type="hidden" name="expiry_month" :value="form.paytr_expiry_month" />
                        <input type="hidden" name="expiry_year" :value="form.paytr_expiry_year" />
                        <input type="hidden" name="cvv" :value="form.paytr_cvv" />
                    </form>
                </template>
            @endif
        </div>
    </section>
@endsection

@push('pre-scripts')
    @if (setting('stripe_enabled') && setting('stripe_integration_type') === 'embedded_form')
        <script defer src="https://js.stripe.com/v3/"></script>
    @endif

    @if (setting('paypal_enabled'))
        <script src="https://www.paypal.com/sdk/js?client-id={{ setting('paypal_client_id') }}&currency={{ setting('default_currency') }}&disable-funding=credit,card,venmo,sepa,bancontact,eps,giropay,ideal,mybank,p24,p24"></script>
    @endif

    @if (setting('paytm_enabled'))
        <script async src="https://securegw{{ setting('paytm_test_mode') ? '-stage' : '' }}.paytm.in/merchantpgpui/checkoutjs/merchants/{{ setting('paytm_merchant_id') }}.js"></script>
    @endif

    @if (setting('razorpay_enabled'))
        <script async src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif

    @if (setting('mercadopago_enabled'))
        <script async src="https://sdk.mercadopago.com/js/v2"></script>
    @endif

    @if (setting('flutterwave_enabled'))
        <script async src="https://checkout.flutterwave.com/v3.js"></script>
    @endif

    @if (setting('paystack_enabled'))
        <script async src="https://js.paystack.co/v1/inline.js"></script>
    @endif

    @if (setting('payfast_enabled'))
        <script async src="https://www.payfast.co.za/onsite/engine.js"></script>
    @endif
@endpush

@push('globals')
    <script>
        FleetCart.stripePublishableKey = '{{ setting("stripe_publishable_key") }}',
        FleetCart.stripeEnabled = {{ setting("stripe_enabled") ? 'true' : 'false' }},
        FleetCart.stripeIntegrationType = '{{ setting("stripe_integration_type") }}',
        FleetCart.langs['storefront::checkout.payment_for_order'] = '{{ trans("storefront::checkout.payment_for_order") }}';
        FleetCart.langs['storefront::checkout.remember_about_your_order'] = '{{ trans("storefront::checkout.remember_about_your_order") }}';
        FleetCart.langs['storefront::checkout.paytr_single_payment'] = '{{ trans("storefront::checkout.paytr_single_payment") }}';
        FleetCart.langs['storefront::checkout.paytr_installments'] = '{{ trans("storefront::checkout.paytr_installments") }}';
        FleetCart.vatRate = {{ (float)(setting('store_vat_rate') ?? 20) }};
        FleetCart.pricesIncludeVat = {{ setting('prices_include_vat') ? 'true' : 'false' }};
        FleetCart.paytrEnabled = {{ setting('paytr_enabled') ? 'true' : 'false' }};
        FleetCart.paytrMode = '{{ setting('paytr_mode') ?: 'iframe' }}';
        FleetCart.paytrInstallmentEnabled = {{ setting('paytr_installment_enabled') ? 'true' : 'false' }};
        FleetCart.paytrMaxInstallment = {{ (int)(setting('paytr_max_installment') ?: 12) }};
        FleetCart.paytrCommissionRate = {{ (float)(setting('paytr_installment_commission_rate') ?: 0) }};
        FleetCart.paytrCommissionToCustomer = {{ setting('paytr_installment_commission_to_customer') ? 'true' : 'false' }};
    </script>

    @vite([
        'modules/Storefront/Resources/assets/public/sass/pages/checkout/create/main.scss',
        'modules/Storefront/Resources/assets/public/js/pages/checkout/create/main.js',
    ])
@endpush
