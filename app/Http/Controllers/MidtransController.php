<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransController extends Controller
{
    public function payment(Request $request)
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$clientKey = config('services.midtrans.clientKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Ambil data pesanan
        $order = Order::where('user_id', auth()->user()->id)->latest()->first();

        // Buat item details untuk Midtrans
        $items = [];
        foreach ($order->cart as $cart) {
            $items[] = [
                'id' => $cart->product->id,
                'price' => $cart->price,
                'quantity' => $cart->quantity,
                'name' => $cart->product->name,
            ];
        }

        // Buat payload untuk Midtrans
        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'email' => $order->email,
                'phone' => $order->phone,
            ],
            'item_details' => $items,
        ];

        try {
            $snapToken = Snap::getSnapToken($payload);
            return view('payment.midtrans', compact('snapToken', 'order'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
