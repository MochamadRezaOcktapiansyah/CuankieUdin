<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Product;
use App\User;
use PDF;
use Notification;
use Illuminate\Support\Str;
use App\Notifications\StatusNotification;
use Midtrans\Config;
use Midtrans\Snap;

class OrderController extends Controller
{
    public function __construct()
    {
        // Set your Merchant Server Key
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        Config::$isProduction = env('MIDTRANS_IS_SANDBOX');
        // Set sanitization on (default)
        Config::$isSanitized = env('MIDTRANS_IS_SANITIZED');
        // Set 3DS transaction for credit card to true
        Config::$is3ds = env('MIDTRANS_IS_3DS');
    }

    public function index()
{
    // Ambil daftar pesanan yang statusnya 'paid' dan urutkan berdasarkan ID descending
    $orders = Order::where('status', 'paid')->orderBy('id', 'DESC')->paginate(10);

    // Loop untuk setiap pesanan dan ambil detail produknya
    foreach ($orders as $order) {
        // Ambil produk yang terkait dengan pesanan ini
        $order->products = $order->cart()->with('product')->get();
    }

    return view('backend.order.index', compact('orders'));
}

    public function create()
    {
        //
    }

    public function store(Request $request)
{
    $this->validate($request, [
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'address1' => 'required|string',
        'address2' => 'nullable|string',
        'coupon' => 'nullable|numeric',
        'phone' => 'required|numeric',
        'post_code' => 'nullable|string',
        'email' => 'required|string',
        'shipping' => 'required|exists:shippings,id',
    ]);

    $cart = Cart::where('user_id', auth()->user()->id)->where('order_id', null)->first();
    if (!$cart) {
        request()->session()->flash('error', 'Cart is Empty!');
        return back();
    }

    $shipping = Shipping::find($request->shipping);
    $order = new Order();
    $order->order_number = 'ORD-' . strtoupper(Str::random(10));
    $order->user_id = auth()->user()->id;
    $order->first_name = $request->first_name;
    $order->last_name = $request->last_name;
    $order->address1 = $request->address1;
    $order->address2 = $request->address2;
    $order->post_code = $request->post_code;
    $order->phone = $request->phone;
    $order->email = $request->email;
    $order->shipping_id = $request->shipping;
    $order->sub_total = $cart->product->price * $cart->quantity;
    $order->quantity = $cart->quantity;
    $order->coupon = session('coupon') ? session('coupon')['value'] : 0;
    $order->total_amount = $order->sub_total + $shipping->price - $order->coupon;
    $order->status = 'new';
    $order->save();

    $cart->order_id = $order->id;
    $cart->save();
    
    $admin = User::where('role', 'admin')->first();
    $details = [
        'title' => 'New order created',
        'actionURL' => route('order.show', $order->id),
        'fas' => 'fa-file-alt'
    ];
    Notification::send($admin, new StatusNotification($details));

    session()->forget('cart');
    session()->forget('coupon');
    Cart::where('user_id', auth()->user()->id)->where('order_id', null)->update(['order_id' => $order->id]);
    request()->session()->flash('success','Your product successfully placed in order');
    return redirect()->route('home');

    // Prepare transaction details for Midtrans
    $transaction_details = [
        'order_id' => $order->order_number,
        'gross_amount' => intval($order->sub_total + $shipping->price), // Include shipping in gross_amount
    ];

    $item_details = [
        [
            'id' => $order->id,
            'price' => intval($order->sub_total),
            'quantity' => $order->quantity,
            'name' => 'Order ' . $order->order_number,
        ],
        [
            'id' => 'SHIPPING',
            'price' => intval($shipping->price),
            'quantity' => 1,
            'name' => 'Shipping Cost',
        ]
    ];

    $customer_details = [
        'first_name' => $order->first_name,
        'last_name' => $order->last_name,
        'email' => $order->email,
        'phone' => $order->phone,
        'address' => $order->address1 . ' ' . $order->address2,
    ];

    $params = [
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'customer_details' => $customer_details,
    ];

    try {
        $snapToken = Snap::getSnapToken($params);
        return view('frontend.pages.checkout', compact('snapToken', 'order'));
    } catch (\Exception $e) {
        return back()->with('error', 'Error processing payment: ' . $e->getMessage());
    }
}
    public function show($id)
    {
        $order = Order::findOrFail($id);
        return view('backend.order.show', compact('order'));
    }

    public function edit($id)
    {
        $order = Order::findOrFail($id);
        return view('backend.order.edit', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'required|in:new,process,delivered,cancel',
        ]);

        $data = $request->all();

        if ($request->status == 'delivered') {
            foreach ($order->cart as $cart) {
                $product = $cart->product;
                $product->stock -= $cart->quantity;
                $product->save();
            }
        }

        $order->fill($data)->save();

        return redirect()->route('order.index')->with('success', 'Order updated successfully.');
    }

    public function destroy($id)
    {
        $order = Order::find($id);
    
        if (!$order) {
            request()->session()->flash('error', 'Order not found');
            return redirect()->back();
        }
    
        // Pastikan hanya pengguna yang memiliki hak akses yang bisa menghapus pesanan
        // Misalnya, jika menggunakan auth, periksa apakah pengguna saat ini memiliki izin untuk menghapus pesanan ini.
    
        // Misalkan Anda memiliki kolom user_id di tabel orders
        if (auth()->user()->isAdmin() || $order->user_id == auth()->user()->id) {
            $status = $order->delete();
    
            if ($status) {
                request()->session()->flash('success', 'Order successfully deleted');
            } else {
                request()->session()->flash('error', 'Failed to delete order');
            }
        } else {
            request()->session()->flash('error', 'You do not have permission to delete this order');
        }
    
        return redirect()->route('order.index');
    }
    
    
    public function orderTrack()
    {
        return view('frontend.pages.order-track');
    }

    public function productTrackOrder(Request $request)
    {
        $order = Order::where('user_id', auth()->user()->id)
                    ->where('order_number', $request->order_number)
                    ->first();

        if (!$order) {
            return redirect()->back()->with('error', 'Invalid order number, please try again.');
        }

        $statusMessage = '';
        switch ($order->status) {
            case 'new':
                $statusMessage = 'Your order has been placed. Please wait.';
                break;
            case 'process':
                $statusMessage = 'Your order is under processing. Please wait.';
                break;
            case 'delivered':
                $statusMessage = 'Your order is successfully delivered.';
                break;
            case 'cancel':
                $statusMessage = 'Your order is canceled. Please try again.';
                break;
        }

        return redirect()->route('home')->with('success', $statusMessage);
    }

    public function pdf(Request $request)
    {
        $order = Order::findOrFail($request->id);
        $fileName = $order->order_number . '-' . $order->first_name . '.pdf';

        $pdf = PDF::loadView('backend.order.pdf', compact('order'));
        return $pdf->download($fileName);
    }

    public function incomeChart(Request $request)
    {
        $year = now()->year;

        $items = Order::with('cart_info')
                    ->whereYear('created_at', $year)
                    ->where('status', 'delivered')
                    ->get()
                    ->groupBy(function ($d) {
                        return now($d->created_at)->format('m');
                    });

        $result = [];
        foreach ($items as $month => $itemCollections) {
            foreach ($itemCollections as $item) {
                $amount = $item->cart_info->sum('amount');
                $m = intval($month);
                isset($result[$m]) ? $result[$m] += $amount : $result[$m] = $amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
