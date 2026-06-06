<?php

namespace Modules\Product\Admin;

use Modules\Admin\Ui\AdminTable;
use Illuminate\Http\JsonResponse;
use Modules\Product\Entities\Product;

class ProductTable extends AdminTable
{
    /**
     * Raw columns that will not be escaped.
     *
     * @var array
     */
    protected array $rawColumns = ['price', 'in_stock'];


    /**
     * Make table response for the resource.
     *
     * @return JsonResponse
     */
    public function make()
    {
        return $this->newTable()
            ->editColumn('thumbnail', function ($product) {
                return view('admin::partials.table.image', [
                    'file' => ($product->variant && $product->variant->base_image->id) ? $product->variant->base_image : $product->base_image,
                ]);
            })
            ->editColumn('price', function (Product $product) {
                $item = $product->variant ?? $product;

                $price = product_price_formatted($item, function ($price, $specialPrice) use ($item) {
                    if ($item->hasSpecialPrice()) {
                        return "<span class='m-r-5'>{$specialPrice}</span>
                            <del class='text-red'>{$price}</del>";
                    }

                    return "<span class='m-r-5'>{$price}</span>";
                });

                if ($item->is_fixed_price) {
                    $price .= "<br><span class='badge badge-info'>" . trans('product::products.fixed_price.badge') . "</span>";
                }

                return $price;
            })
            ->editColumn('in_stock', function (Product $product) {
                $item = $product->variant ?? $product;
                $in_stock = $item->in_stock && (!$item->manage_stock || $item->qty > 0);

                return $in_stock ? "<span class='badge badge-primary'>" . trans('product::products.form.stock_availability_states.1') . "</span>" : "<span class='badge badge-danger'>" . trans('product::products.form.stock_availability_states.0') . "</span>";
            });
    }
}
