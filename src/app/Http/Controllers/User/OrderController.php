<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Admin\Controllers\PackageController;
use App\Events\UserNotification;
use App\Models\Package;
use App\Models\Cart;
use App\Models\Delivery;
use App\Models\LiveShare;
use App\Models\Order;
use App\Models\Card;
use App\Models\OrderDetails;
use App\Enums\OrderStatus as oStatus;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Session;
use Stripe;


class OrderController extends Controller
{
        public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('isNormalUser');
    }
    public function index() {
        $packages = Package::all() ?? [];
        return view("User.Order.order", ['packages' => $packages]);
    }

    public function addToCart(Request $request) {
        $cart = new Cart;
        $cart->user_id = $request->id;
        $cart->package_id = $request->package_id;
        $cart->save();
        return redirect()->route('order')->with('success', 'package has added to the cart successfully!');

    }
    public static function countCartItems() {
        return Cart::where('user_id', Auth::id())->count();
    }

    public function summary($id) {
        $items = Cart::all()->where('user_id', $id) ?? [];
        if(count($items) == 0) return redirect()->route('order');
        $deliveryTypes = Delivery::all();
        return view("User.Order.order-summary", ['items' => $items, 'deliveryTypes' => $deliveryTypes]);
    }
    
    public function deleteItem($id) {
        Cart::where('id', $id)->delete();
        return redirect()->route('order-summary');
    }

    public function checkout($totalPackagesPrice, Request $request) {
        $deliveryType = Delivery::all()->where('id', $request->deliveryType)->first();
        $totalPrice = $totalPackagesPrice +  $deliveryType['price'];
        return view("User.Order.checkout", ['totalPackagesPrice' => $totalPrice, 'deliveryId' => $deliveryType['id']]);
    }

    public function currentOrder() {
        $orders = Order::where('user_id', Auth::id())
        ->select('id','total_price', 'status_id', 'payment_status')
        ->where('status_id', '!=', oStatus::COMPLETED->value)
        ->where('status_id', '!=', oStatus::CANCEL->value)
        ->get();
        return view('User.Order.current-order', ['orders' => $orders]);
    }
    public function cashPayment($totalPackagesPrice, Request $request) {
        $packagesPrice = 0;
        $order = new Order;
        $order->user_id = Auth::id();
        $order->total_price = $request->totalPackagesPrice;
        $order->delivery_id = $request->deliveryId;
        /*$order->cc_name = "none";
        $order->cc_number = "none";
        $order->cc_exp = "none";
        $order->x_card_code = "none";*/
        $order->payment_status = "Cash On Delivery";
        $cart = cart::where('user_id', Auth::id())
        ->get();
        $order->save();
        $counter = 0;
        foreach ($cart as $item) {
            $orderDetail = new OrderDetails();
            $orderDetail->order_id = $order->id;
            $orderDetail->package_id = $item->packages->id;
            $orderDetail->item = $item->packages->name;
            foreach ($item->packages->services as $service) {
                $packagesPrice += $service->price;
            }
            $orderDetail->price = $packagesPrice;
            $orderDetail->save();
            $counter++;
        }
        DB::table('carts')->where('user_id',Auth::id())->delete();
        
        event(new UserNotification($order->user_id, $order->id ,'new Request has send'));
        return redirect()->route('current-order')->with('success', 'You order has placed successfully!');
        
    //
    }
    public function cardPayment($totalPackagesPrice, Request $request) {
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        Stripe\Charge::create ([
                "amount" => $totalPackagesPrice * 100,
                "currency" => "MYR",
                "source" => $request->stripeToken,
                "description" => "Thanks for pay"
        ]);
        
        /* Validate the request...
        $this->validate($request, [
            'cc_name' => 'required',
            'cc_number' => 'required',
            'cc_exp' => 'required',
            'x_card_code' => 'required|numeric|digits:6',
        ]);
        
        $card_payment = new Card();
        $card_payment->user_id = Auth::id();
        $card_payment->cc_name = $request->cc_name;
        $card_payment->cc_number = $request->cc_number;
        $card_payment->cc_exp = $request->cc_exp;
        $card_payment->x_card_code = $request->x_card_code;
        $card_payment->save();*/
        
        $packagesPrice = 0;
        $order = new Order;
        $order->user_id = Auth::id();
        $order->total_price = $request->totalPackagesPrice;
        $order->delivery_id = $request->deliveryId;
        $order->payment_status = "Paid" ;
        $cart = cart::where('user_id', Auth::id())
        ->get();
        $order->save();
        $counter = 0;
        foreach ($cart as $item) {
            $orderDetail = new OrderDetails();
            $orderDetail->order_id = $order->id;
            $orderDetail->package_id = $item->packages->id;
            $orderDetail->item = $item->packages->name;
            foreach ($item->packages->services as $service) {
                $packagesPrice += $service->price;
            }
            $orderDetail->price = $packagesPrice;
            $orderDetail->save();
            $counter++;
        }
        DB::table('carts')->where('user_id',Auth::id())->delete();
        return redirect()->route('current-order')->with('success', 'You order has placed successfully!');
        //Session::flash('success', 'Payment successful!');
        //return back();
    }
    public function tracker(Request $request  ,$id) { // view order detail, when the order is assigned to a driver

        $order = Order::all()->where('id', $id)->first();
        if(!isset($order)) return redirect()->route('current-order'); // if user didn't has any order
        if(Order::isDriverAssigned($id))
            return view('User.Order.track-order', ['order' => $order]);
        return view('User.Order.order-detail', ['order' => $order]);
        
    }

    public function historyDetail($id) {
        $order = Order::all()->where('id', $id)->first();
        return view('User.Order.history-detail', ['order' => $order]);


    }
    public function cancel($id) {
        OrderDetails::where('id', $id)->delete();
        Order::where('id', $id)->delete();
        return redirect()->route('current-order')->with('success', 'Order cancelled successfully!');
    }
    public function trail() {
        return view('User.Order.track');
    }
    public function test($id) {
        $liveLocation = LiveShare::all()->where('driver_id', $id)->first();
        return response()->json([
            'location' => $liveLocation
        ]);
    }

    public function history()
    {
        $order = Order::all()->where('user_id',Auth::id())->where('status_id', oStatus::COMPLETED->value);
        return view('User.Order.history', ['order' => $order]);
    }
}
