<?php

namespace Modules\Payment\Responses;

use Modules\Order\Entities\Order;
use Modules\Payment\GatewayResponse;

class PayTRResponse extends GatewayResponse
{
    private Order $order;
    private array $data;

    public function __construct(Order $order, array $data)
    {
        $this->order = $order;
        $this->data  = $data;
    }

    public function getOrderId()
    {
        return $this->order->id;
    }

    public function toArray(): array
    {
        return array_merge(['orderId' => $this->order->id], $this->data);
    }
}
