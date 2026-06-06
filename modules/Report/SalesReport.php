<?php

namespace Modules\Report;

use Modules\Order\Entities\Order;
use Illuminate\Support\Facades\DB;

class SalesReport extends Report
{
    protected function view()
    {
        return 'report::admin.reports.sales_report.index';
    }


    protected function query()
    {
        return Order::selectRaw('orders.id')
            ->selectRaw('MIN(created_at) as start_date')
            ->selectRaw('MAX(created_at) as end_date')
            ->selectRaw('COUNT(*) as total_orders')
            ->join(DB::raw('(SELECT order_id, sum(qty) qty FROM order_products GROUP BY order_id) op'), function ($join) {
                $join->on('orders.id', '=', 'op.order_id');
            })
            ->selectRaw('SUM(op.qty) as total_products')
            ->selectRaw('SUM(orders.sub_total * orders.currency_rate) as sub_total')
            ->selectRaw('SUM(orders.shipping_cost * orders.currency_rate) as shipping_cost')
            ->selectRaw('SUM(orders.discount * orders.currency_rate) as discount')
            ->leftJoin(DB::raw('(SELECT order_id, sum(amount) amount FROM order_taxes GROUP BY order_id) ot'), function ($join) {
                $join->on('orders.id', '=', 'ot.order_id');
            })
            ->selectRaw('SUM(ot.amount * orders.currency_rate) as tax')
            ->selectRaw('SUM(orders.total * orders.currency_rate) as total')
            ->groupBy('orders.id');
    }
}
