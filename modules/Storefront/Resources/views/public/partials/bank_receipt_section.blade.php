@if ($order->getRawOriginal('payment_method') === 'bank_transfer')
    <div class="bank-receipt-section" style="margin-top: 24px; padding: 20px; border: 1px solid #e0e0e0; border-radius: 6px;">
        <h4>{{ trans('storefront::account.receipt.section_title') }}</h4>

        @if (setting('bank_transfer_instructions'))
            <div class="bank-transfer-info" style="margin-bottom: 16px; color: #555;">
                {!! setting('bank_transfer_instructions') !!}
            </div>
        @endif

        @if ($order->bank_receipt_status === 'approved')
            <div class="alert alert-success">
                {{ trans('storefront::account.receipt.status_approved') }}
            </div>
        @elseif ($order->bank_receipt_status === 'rejected')
            <div class="alert alert-danger">
                {{ trans('storefront::account.receipt.status_rejected') }}
                @if ($order->bank_receipt_admin_note)
                    <br><small>{{ trans('storefront::account.receipt.admin_note') }}: {{ $order->bank_receipt_admin_note }}</small>
                @endif
            </div>
        @elseif ($order->bank_receipt_status === 'pending')
            <div class="alert alert-info">
                {{ trans('storefront::account.receipt.status_pending') }}
            </div>
        @endif

        @if (!in_array($order->bank_receipt_status, ['approved']))
            <form
                action="{{ route('account.orders.receipt.store', $order->id) }}"
                method="POST"
                enctype="multipart/form-data"
                style="margin-top: 12px;"
            >
                @csrf

                <div class="form-group">
                    <label>{{ trans('storefront::account.receipt.upload_label') }}</label>
                    <input
                        type="file"
                        name="receipt"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="form-control"
                        required
                    >

                    @error('receipt')
                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    {{ trans('storefront::account.receipt.upload_btn') }}
                </button>
            </form>
        @endif
    </div>
@endif
