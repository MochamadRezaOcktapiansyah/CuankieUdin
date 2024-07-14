<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\Cart;
use App\Models\Order;
use Helper;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;


class CartController extends Controller
{
    protected $product = null;

    public function __construct(Product $product)
    {
         // Set Midtrans configuration
         Config::$serverKey = config('services.midtrans.serverKey');
         Config::$isProduction = config('services.midtrans.isProduction');
         Config::$isSanitized = config('services.midtrans.isSanitized');
         Config::$is3ds = config('services.midtrans.is3ds');
        $this->product = $product;

    }

    public function addToCart(Request $request){
        // dd($request->all());
        if (empty($request->slug)) {
            request()->session()->flash('error','Invalid Products');
            return back();
        }        
        $product = Product::where('slug', $request->slug)->first();
        // return $product;
        if (empty($product)) {
            request()->session()->flash('error','Invalid Products');
            return back();
        }
    
        $already_cart = Cart::where('user_id', auth()->user()->id)->where('order_id',null)->where('product_id', $product->id)->first();
        // return $already_cart;
        if($already_cart) {
            // dd($already_cart);
            $already_cart->quantity = $already_cart->quantity + 1;
            $already_cart->amount = $product->price+ $already_cart->amount;
            // return $already_cart->quantity;
            if ($already_cart->product->stock < $already_cart->quantity || $already_cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
            $already_cart->save();
            
        }else{
            
            $cart = new Cart;
            $cart->user_id = auth()->user()->id;
            $cart->product_id = $product->id;
            $cart->price = ($product->price-($product->price*$product->discount)/100);
            $cart->quantity = 1;
            $cart->amount=$cart->price*$cart->quantity;
            if ($cart->product->stock < $cart->quantity || $cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
            $cart->save();
            $wishlist=Wishlist::where('user_id',auth()->user()->id)->where('cart_id',null)->update(['cart_id'=>$cart->id]);
        }
        request()->session()->flash('success','Product successfully added to cart');
        return back();       
    }  
    
    public function singleAddToCart(Request $request){
        $request->validate([
            'slug'      =>  'required',
            'quant'      =>  'required',
        ]);
        // dd($request->quant[1]);
    
    
        $product = Product::where('slug', $request->slug)->first();
        if($product->stock <$request->quant[1]){
            return back()->with('error','Out of stock, You can add other products.');
        }
        if ( ($request->quant[1] < 1) || empty($product) ) {
            request()->session()->flash('error','Invalid Products');
            return back();
        }    
    
        $already_cart = Cart::where('user_id', auth()->user()->id)->where('order_id',null)->where('product_id', $product->id)->first();
    
        // return $already_cart;
    
        if($already_cart) {
            $already_cart->quantity = $already_cart->quantity + $request->quant[1];
            // $already_cart->price = ($product->price * $request->quant[1]) + $already_cart->price ;
            $already_cart->amount = ($product->price * $request->quant[1])+ $already_cart->amount;
    
            if ($already_cart->product->stock < $already_cart->quantity || $already_cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
    
            $already_cart->save();
            
        }else{
            
            $cart = new Cart;
            $cart->user_id = auth()->user()->id;
            $cart->product_id = $product->id;
            $cart->price = ($product->price-($product->price*$product->discount)/100);
            $cart->quantity = $request->quant[1];
            $cart->amount=($product->price * $request->quant[1]);
            if ($cart->product->stock < $cart->quantity || $cart->product->stock <= 0) return back()->with('error','Stock not sufficient!.');
            // return $cart;
            $cart->save();
        }
        request()->session()->flash('success','Product successfully added to cart.');
        return back();       
    } 
    
    public function cartDelete(Request $request){
        $cart = Cart::find($request->id);
        if ($cart) {
            $cart->delete();
            request()->session()->flash('success','Cart successfully removed');
            return back();  
        }
        request()->session()->flash('error','Error please try again');
        return back();       
    }     
    
    public function cartUpdate(Request $request){
        // dd($request->all());
        if($request->quant){
            $error = array();
            $success = '';
            // return $request->quant;
            foreach ($request->quant as $k=>$quant) {
                // return $k;
                $id = $request->qty_id[$k];
                // return $id;
                $cart = Cart::find($id);
                // return $cart;
                if($quant > 0 && $cart) {
                    // return $quant;
    
                    if($cart->product->stock < $quant){
                        request()->session()->flash('error','Out of stock');
                        return back();
                    }
                    $cart->quantity = ($cart->product->stock > $quant) ? $quant  : $cart->product->stock;
                    // return $cart;
                    
                    if ($cart->product->stock <=0) continue;
                    $after_price=($cart->product->price-($cart->product->price*$cart->product->discount)/100);
                    $cart->amount = $after_price * $quant;
                    // return $cart->price;
                    $cart->save();
                    $success = 'Cart successfully updated!';
                }else{
                    $error[] = 'Cart Invalid!';
                }
            }
            return back()->with($error)->with('success', $success);
        }else{
            return back()->with('Cart Invalid!');
        }    
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        $carts = Cart::where('user_id', $user->id)
                     ->where('order_id', null)
                     ->get();

        if ($carts->isEmpty()) {
            return back()->with('error', 'Your cart is empty.');
        }

          // Get shipping options
    $shippingOptions = Helper::shipping(); // Assuming Helper::shipping() fetches shipping options

    // Initialize shipping cost
    $shippingCost = 0;

    if (count($shippingOptions) > 0) {
        // Assign shipping cost from the first shipping option
        $shippingCost = $shippingOptions[0]->price; // Adjust this logic as per your actual shipping option selection
    }

    // Calculate total amount including shipping
    $subTotal = $carts->sum('amount');
    $totalAmount = $subTotal + $shippingCost;
        $orderData = [
            'order_number' => strtoupper(Str::random(10)),
            'user_id' => $user->id,
            'sub_total' => $subTotal,
            'total_amount' => $subTotal + $shippingCost, // Include shipping cost
            'status' => 'new',
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address1' => $user->address,
        ];

        session(['order_data' => $orderData, 'cart_data' => $carts]);

        $transactionDetails = [
            'order_id' => $orderData['order_number'],
            'gross_amount' => number_format($orderData['total_amount'], 0, '', ''),
        ];

        $itemDetails = [];
        foreach ($carts as $cart) {
            $itemDetails[] = [
                'id' => $cart->product_id,
                'price' => number_format($cart->price, 0, '', ''),
                'quantity' => $cart->quantity,
                'name' => $cart->product->title,
            ];
        }

        // Add shipping cost to item details
        if ($shippingCost > 0) {
            $itemDetails[] = [
                'id' => 'SHIPPING',
                'price' => $shippingCost,
                'quantity' => 1,
                'name' => 'Shipping Cost',
            ];
        }

        $customerDetails = [
            'first_name' => $orderData['first_name'],
            'last_name' => $orderData['last_name'],
            'email' => $orderData['email'],
            'phone' => $orderData['phone'],
            'address' => $orderData['address1'],
        ];

        $params = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return view('frontend.pages.checkout', compact('snapToken'));
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }
    public function paymentCallback(Request $request)
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed == $request->signature_key) {
            $order_number = $request->order_id;
            $transaction_status = $request->transaction_status;

            if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
                // Pembayaran berhasil
                $order_data = session('order_data');
                $order = new Order();
                $order->fill($order_data);
                $order->save();

                $cart_data = session('cart_data');
                foreach ($cart_data as $cart) {
                    $cart->order_id = $order->id;
                    $cart->save();
                }

                session()->forget(['order_data', 'cart_data']);
                return redirect()->route('order.index')->with('success', 'Order completed successfully.');
            } elseif ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
                // Pembayaran gagal atau dibatalkan
                return redirect()->route('checkout')->with('error', 'Payment failed or canceled.');
            }
        }

        return redirect()->route('checkout')->with('error', 'Invalid signature.');
    }
}
