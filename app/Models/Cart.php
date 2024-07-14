<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Order;

class Cart extends Model
{
    protected $fillable = ['user_id', 'product_id', 'order_id', 'quantity', 'amount', 'price', 'status'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order(){
        return $this->belongsTo(Order::class, 'order_id');
    }
}
