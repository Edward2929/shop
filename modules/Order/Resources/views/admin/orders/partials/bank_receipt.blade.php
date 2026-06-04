@if ($order->getRawOriginal('payment_method') === 'bank_transfer')
    <div class="box" style="margin-top: 20px;">
        <div class="box-header">
            <h3 class="box-title">{{ trans('order::orders.bank_receipt') }}</h3>
        </div>

        <div class="box-body">
            @if ($order->bank_receipt_path)
                <table class="table">
                    <tbody>
                        <tr>
                            <td>{{ trans('order::orders.receipt_uploaded_at') }}</td>
                            <td>{{ $order->bank_receipt_uploaded_at?->toFormattedDateString() }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                @if ($order->bank_receipt_status === 'approved')
                                    <span class="label label-success">{{ trans('order::orders.receipt_status_approved') }}</span>
                                @elseif ($order->bank_receipt_status === 'rejected')
                                    <span class="label label-danger">{{ trans('order::orders.receipt_status_rejected') }}</span>
                                @else
                                    <span class="label label-warning">{{ trans('order::orders.receipt_status_pending') }}</span>
                                @endif
                            </td>
                        </tr>
                        @if ($order->bank_receipt_admin_note)
                            <tr>
                                <td>{{ trans('order::orders.receipt_admin_note') }}</td>
                                <td>{{ $order->bank_receipt_admin_note }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <div style="margin-bottom: 12px;">
                    <a href="{{ route('admin.orders.receipt.download', $order) }}" class="btn btn-default" target="_blank">
                        <i class="fa fa-download"></i> {{ trans('order::orders.receipt_download') }}
                    </a>
                </div>

                @if ($order->bank_receipt_status !== 'approved')
                    <div class="bank-receipt-actions" x-data="{ note: '' }">
                        <div class="form-group">
                            <label>{{ trans('order::orders.receipt_admin_note') }}</label>
                            <input type="text" class="form-control" x-model="note" placeholder="{{ trans('order::orders.receipt_admin_note') }}">
                        </div>

                        <button
                            type="button"
                            class="btn btn-success"
                            @click="
                                fetch('{{ route('admin.orders.receipt.approve', $order) }}', {
                                    method: 'PUT',
                                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                                    body: JSON.stringify({ admin_note: note })
                                }).then(r => r.json()).then(d => { notify(d.message); setTimeout(() => location.reload(), 1500); })
                            "
                        >
                            <i class="fa fa-check"></i> {{ trans('order::orders.receipt_approve') }}
                        </button>

                        <button
                            type="button"
                            class="btn btn-danger"
                            style="margin-left: 8px;"
                            @click="
                                fetch('{{ route('admin.orders.receipt.reject', $order) }}', {
                                    method: 'PUT',
                                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                                    body: JSON.stringify({ admin_note: note })
                                }).then(r => r.json()).then(d => { notify(d.message); setTimeout(() => location.reload(), 1500); })
                            "
                        >
                            <i class="fa fa-times"></i> {{ trans('order::orders.receipt_reject') }}
                        </button>
                    </div>
                @endif
            @else
                <p class="text-muted">{{ trans('order::orders.receipt_not_uploaded') }}</p>
            @endif
        </div>
    </div>
@endif
