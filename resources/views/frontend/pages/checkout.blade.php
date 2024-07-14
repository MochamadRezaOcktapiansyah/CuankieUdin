@extends('frontend.layouts.master')

@section('title','Checkout page')

@section('main-content')

<!-- Breadcrumbs -->
<div class="breadcrumbs">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="bread-inner">
                    <ul class="bread-list">
                        <li><a href="{{route('home')}}">Home<i class="ti-arrow-right"></i></a></li>
                        <li class="active"><a href="javascript:void(0)">Checkout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Breadcrumbs -->

<!-- Start Checkout -->
<section class="shop checkout section">
    <div class="container">
        <form class="form" method="POST" action="{{route('cart.order')}}" id="checkout-form">
            @csrf
            <div class="row">

                <div class="col-lg-8 col-12">
                    <div class="checkout-form">
                        <h2>Make Your Checkout Here</h2>
                        <p>Please register in order to checkout more quickly</p>
                        <!-- Form -->
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>First Name<span>*</span></label>
                                    <input type="text" name="first_name" placeholder="" value="{{old('first_name')}}">
                                    @error('first_name')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>Last Name<span>*</span></label>
                                    <input type="text" name="last_name" placeholder="" value="{{old('last_name')}}">
                                    @error('last_name')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>Email Address<span>*</span></label>
                                    <input type="email" name="email" placeholder="" value="{{old('email')}}">
                                    @error('email')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>Phone Number <span>*</span></label>
                                    <input type="number" name="phone" placeholder="" required value="{{old('phone')}}">
                                    @error('phone')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>Address Line 1<span>*</span></label>
                                    <input type="text" name="address1" placeholder="" value="{{old('address1')}}">
                                    @error('address1')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>Address Line 2</label>
                                    <input type="text" name="address2" placeholder="" value="{{old('address2')}}">
                                    @error('address2')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="post_code" placeholder="" value="{{old('post_code')}}">
                                    @error('post_code')
                                    <span class='text-danger'>{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <!--/ End Form -->
                    </div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="order-details">
                        <!-- Order Widget -->
                        <div class="single-widget">
                            <h2>CART TOTALS</h2>
                            <div class="content">
                                <ul>
                                    <li class="order_subtotal" data-price="{{Helper::totalCartPrice()}}">Cart Subtotal<span>Rp {{number_format(Helper::totalCartPrice(),0, ',', '.')}}</span></li>
                                    <li class="shipping">
                                        Shipping Cost
                                        @if(count(Helper::shipping())>0 && Helper::cartCount()>0)
                                        <select name="shipping" class="nice-select">
                                            <option value="">Select your address</option>
                                            @foreach(Helper::shipping() as $shipping)
                                            <option value="{{$shipping->id}}" class="shippingOption" data-price="{{$shipping->price}}">{{$shipping->type}}: Rp {{number_format($shipping->price, 0, ',', '.')}}</option>
                                            @endforeach
                                        </select>
                                        @else 
                                        <span>Free</span>
                                        @endif
                                    </li>
                                    
                                    @if(session('coupon'))
                                    <li class="coupon_price" data-price="{{session('coupon')['value']}}">You Save<span>Rp {{number_format(session('coupon')['value'],0, ',', '.')}}</span></li>
                                    @endif
                                    @php
                                    $total_amount=Helper::totalCartPrice();
                                    if(session('coupon')){
                                        $total_amount=$total_amount-session('coupon')['value'];
                                    }
                                    @endphp
                                    @if(session('coupon'))
                                    <li class="last" id="order_total_price">Total<span>Rp {{number_format($total_amount,0, ',', '.')}}</span></li>
                                    @else
                                    <li class="last" id="order_total_price">Total<span>Rp {{number_format($total_amount,0, ',', '.')}}</span></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <!--/ End Order Widget -->
                        <!-- Button Widget -->
                        <div class="single-widget get-button">
                            <div class="content">
                                <div class="button">
                                    <button type="button" class="btn" id="pay-button">Proceed to Checkout</button>
                                </div>
                            </div>
                        </div>
                        <!--/ End Button Widget -->
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
<!--/ End Checkout -->

@endsection

@push('styles')
    <style>
        li.shipping{
            display: inline-flex;
            width: 100%;
            font-size: 14px;
        }
        li.shipping .input-group-icon {
            width: 100%;
            margin-left: 10px;
        }
        .input-group-icon .icon {
            position: absolute;
            left: 20px;
            top: 0;
            line-height: 40px;
            z-index: 3;
        }
        .form-select {
            height: 30px;
            width: 100%;
        }
        .form-select .nice-select {
            border: none;
            border-radius: 0px;
            height: 40px;
            background: #f6f6f6 !important;
            padding-left: 45px;
            padding-right: 40px;
            width: 100%;
        }
        .list li{
            margin-bottom:0 !important;
        }
        .list li:hover{
            background:#F7941D !important;
            color:white !important;
        }
        .form-select .nice-select::after {
            top: 14px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{asset('frontend/js/nice-select/js/jquery.nice-select.min.js')}}"></script>
    <script src="{{ asset('frontend/js/select2/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() { $("select.select2").select2(); });
        $('select.nice-select').niceSelect();
    </script>
    <script>
    $(document).ready(function() {
    $('.shipping select[name=shipping]').change(function(){
        let cost = parseFloat($(this).find('option:selected').data('price')) || 0;
        let subtotal = parseFloat($('.order_subtotal').data('price'));
        let coupon = parseFloat($('.coupon_price').data('price')) || 0;
        let totalAmount = subtotal + cost - coupon;

        // Update teks span total
        $('#order_total_price span').text('Rp ' + formatRupiah(totalAmount));
    });

    // Fungsi untuk format ke Rupiah
    function formatRupiah(angka) {
        var number_string = angka.toString(),
            sisa = number_string.length % 3,
            rupiah = number_string.substr(0, sisa),
            ribuan = number_string.substr(sisa).match(/\d{3}/g);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }
});
    </script>
@endpush

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.clientKey') }}"></script>
<script type="text/javascript">
    var payButton = document.getElementById('pay-button');
    payButton.addEventListener('click', function () {
        snap.pay('{{ $snapToken }}', {
            onSuccess: function(result) {
                document.getElementById('checkout-form').submit();
            },
            onPending: function(result) {
                document.getElementById('checkout-form').submit();
            },
            onError: function(result) {
                alert('Payment failed: ' + result.status_message);
            }
        });

        $(document).on('hidden.bs.modal', function (e) {
            window.location.reload();
        });
    });
</script>
@endpush
