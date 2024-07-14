@extends('frontend.layouts.master')

@section('title', 'Midtrans Payment')

@section('main-content')

    <!-- Display Snap Token -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Payment</div>
                    <div class="card-body">
                        <p>Your payment is being processed. Please wait...</p>
                        <form id="payment-form" method="post" action="{{ $snapToken }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Pay Now</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection