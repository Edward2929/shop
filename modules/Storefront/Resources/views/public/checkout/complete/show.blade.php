@extends('storefront::public.layout')

@section('content')
    <section class="order-complete-wrap">
        <div class="container">
            <div class="order-complete-wrap-inner">
                <div class="order-complete">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>

                    <h2>{{ trans('storefront::order_complete.order_placed') }}</h2>
                    <span>{!! trans('storefront::order_complete.your_order_has_been_placed', ['id' => $order->id]) !!}</span>
                </div>

                {{-- Order Summary --}}
                <div class="order-complete-summary" style="margin-top: 40px; max-width: 860px; margin-left: auto; margin-right: auto;">

                    {{-- Items --}}
                    <div class="order-complete-section" style="margin-bottom: 32px;">
                        <h3 class="section-title">{{ trans('storefront::order_complete.items_ordered') }}</h3>

                        <div class="table-responsive">
                            <table class="table table-borderless order-details-table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('storefront::account.product_name') }}</th>
                                        <th>{{ trans('storefront::account.view_order.unit_price') }}</th>
                                        <th>{{ trans('storefront::account.view_order.quantity') }}</th>
                                        <th>{{ trans('storefront::account.view_order.line_total') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($order->products as $product)
                                        <tr>
                                            <td>
                                                <a href="{{ $product->url() }}" class="product-name">{{ $product->name }}</a>

                                                @if ($product->hasAnyVariation())
                                                    <ul class="list-inline product-options">
                                                        @foreach ($product->variations as $variation)
                                                            <li>
                                                                <label>{{ $variation->name }}:</label>
                                                                {{ $variation->values()->first()?->label }}{{ $loop->last ? '' : ',' }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </td>

                                            <td>{{ $product->unit_price->convert($order->currency, $order->currency_rate)->format($order->currency) }}</td>
                                            <td>{{ $product->qty }}</td>
                                            <td>{{ $product->line_total->convert($order->currency, $order->currency_rate)->format($order->currency) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Totals + Address --}}
                    <div class="row" style="margin-bottom: 32px;">
                        <div class="col-md-6">
                            <h3 class="section-title">{{ trans('storefront::order_complete.order_totals') }}</h3>

                            <ul class="list-inline order-summary-list">
                                <li>
                                    <label>{{ trans('storefront::account.view_order.subtotal') }}</label>
                                    <span>{{ $order->sub_total->convert($order->currency, $order->currency_rate)->format($order->currency) }}</span>
                                </li>

                                @if ($order->hasShippingMethod())
                                    <li>
                                        <label>{{ $order->shipping_method }}</label>
                                        <span>{{ $order->shipping_cost->convert($order->currency, $order->currency_rate)->format($order->currency) }}</span>
                                    </li>
                                @endif

                                @foreach ($order->taxes as $tax)
                                    <li>
                                        <label>{{ $tax->name }}</label>
                                        <span>{{ $tax->order_tax->amount->convert($order->currency, $order->currency_rate)->format($order->currency) }}</span>
                                    </li>
                                @endforeach

                                @if ($order->hasCoupon())
                                    <li>
                                        <label>{{ trans('storefront::account.view_order.coupon') }} ({{ $order->coupon->code }})</label>
                                        <span>-{{ $order->discount->convert($order->currency, $order->currency_rate)->format($order->currency) }}</span>
                                    </li>
                                @endif
                            </ul>

                            <div class="order-summary-total">
                                <label>{{ trans('storefront::account.view_order.total') }}</label>
                                <span class="total-price">{{ $order->total->convert($order->currency, $order->currency_rate)->format($order->currency) }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h3 class="section-title">{{ trans('storefront::order_complete.delivery_address') }}</h3>

                            <address>
                                <span>{{ $order->shipping_full_name }}</span>
                                <span>{{ $order->shipping_address_1 }}</span>
                                @if ($order->shipping_address_2)
                                    <span>{{ $order->shipping_address_2 }}</span>
                                @endif
                                <span>{{ $order->shipping_city }}, {!! $order->shipping_state_name !!} {{ $order->shipping_zip }}</span>
                                <span>{{ $order->shipping_country_name }}</span>
                            </address>
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    <div class="order-complete-section" style="margin-bottom: 32px;">
                        <h3 class="section-title">{{ trans('storefront::order_complete.payment_info') }}</h3>

                        <ul class="list-inline order-summary-list">
                            <li>
                                <label>{{ trans('storefront::order_complete.payment_method') }}</label>
                                <span>{{ $order->payment_method }}</span>
                            </li>

                            {{-- PayTR installment info --}}
                            @if ($order->getRawOriginal('payment_method') === 'paytr' || $order->paytr_status)
                                @if ($order->paytr_payment_type === 'installment' && $order->paytr_installment_count > 1)
                                    <li>
                                        <label>{{ trans('storefront::order_complete.paytr_payment_type') }}</label>
                                        <span>{{ trans('storefront::order_complete.paytr_installment_count', ['count' => $order->paytr_installment_count]) }}</span>
                                    </li>

                                    @if ($order->paytr_installment_amount)
                                        <li>
                                            <label>{{ trans('storefront::order_complete.paytr_installment_amount') }}</label>
                                            <span>{{ number_format($order->paytr_installment_amount, 2) }} {{ $order->currency }}</span>
                                        </li>
                                    @endif

                                    @if ($order->paytr_total_paid)
                                        <li>
                                            <label>{{ trans('storefront::order_complete.paytr_total_paid') }}</label>
                                            <span>{{ number_format($order->paytr_total_paid, 2) }} {{ $order->currency }}</span>
                                        </li>
                                    @endif
                                @else
                                    <li>
                                        <label>{{ trans('storefront::order_complete.paytr_payment_type') }}</label>
                                        <span>{{ trans('storefront::order_complete.paytr_single') }}</span>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </div>

                    {{-- Bank Transfer: show bank info + receipt upload --}}
                    @if ($order->getRawOriginal('payment_method') === 'bank_transfer')
                        @include('storefront::public.partials.bank_receipt_section', ['order' => $order])
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('globals')
    @vite([
        'modules/Storefront/Resources/assets/public/sass/pages/checkout/complete/main.scss',
    ])
@endpush
