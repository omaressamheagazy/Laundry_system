<?php

namespace App\Http\Controllers\Driver;

use App\Enums\OrderStatus as EnumsOrderStatus;
use App\Helpers\DistanceCalculator;
use Carbon\Carbon;
use App\Models\Order;
use App\Helpers\Location;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Laundry;
use App\Models\User;
use App\Enums\OrderStatus as oStatus;
use App\Events\DriverNotification;
use App\Events\SendLocation;
use App\Models\LiveLocation;
use App\Models\LiveShare;
use App\Models\tracker;
use Doctrine\DBAL\Driver\IBMDB2\Driver;
use Illuminate\Support\Facades\Auth;

use function App\Enums\getOrderMessage;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('hasValidDocuments');
    }

    public function newRequest()
    {
        $newRequests = Order::all()->where('status_id', oStatus::SEARCHING_FOR_DRIVER->value);
        return view('Driver.Order.new-request', ['newRequests' => $newRequests]);
    }
    public function currentOrder()
    {
        $tracker = tracker::all()->where('driver_id', Auth::id());
        if($tracker->first() != NULL) { // ensure that it has current order
            
            $currentRequests = $tracker->first()->orders::all()->where('status_id', '!=', oStatus::COMPLETED->value)
                ->where('status_id', '!=', oStatus::SEARCHING_FOR_DRIVER->value)
                ->where('status_id', '!=', oStatus::CANCEL->value);
            return view('Driver.Order.current-order', ['order' => $currentRequests]);
        }
        return redirect()->route('newRequest');
    }
    public function requestDetail($id)
    {
        $order = Order::all()->where('id', $id)->first();
        if ($order->status_id != oStatus::SEARCHING_FOR_DRIVER->value)
            return redirect()->route('newRequest')->with('warning', 'you are not allowed to enter this page');
        $sortedLaundries = Laundry::sortByNearestDistance($id);
        if (!isset($order)) return redirect()->route('newRequest'); // if user didn't has any order
        return view('Driver.Order.request-detail', ['order' => $order, 'laundries' => $sortedLaundries]);
    }

    public function trackOrder(Request $request, $id = '')
    {
        $order = Order::all()->where('id', $request->id == NULL ? $id : $request->id)->first();
        $user = $order->user->addresses->where('default_address', 1)->first();


        if ($request->isMethod('get')) {
            if (($order->status_id == oStatus::SEARCHING_FOR_DRIVER->value)) return redirect()->route('newRequest');
            if (($order->status_id == oStatus::CANCEL->value) || ($order->status_id == oStatus::COMPLETED->value))
                return redirect()->route('history');
        }
        // when the driver accept the current order
        $sortedLaundries = Laundry::getDistance($user->latitude, $user->longitude);
        $currentOrderStatus = $order->status_id;
        $orderStatus = OrderStatus::filterStatus($currentOrderStatus);

        if ($request->isMethod('post')) { // when the driver accept the order
            tracker::updateOrCreate(
                ['order_id' => $request->id],
                ['driver_id' => Auth::id()],
            );
            Order::changeOrderStatus($request->id, oStatus::DRIVER_ASSIGNED->value);
        }
        return view('Driver.Order.track-order', ['order' => $order, 'laundries' => $sortedLaundries, 'orderStatus' => $orderStatus]);
    }


    public function orderAction(Request $request)
    {
        if ($request->status == oStatus::CANCEL->value) {
            tracker::where('order_id', $request->orderId)->delete();
            Order::changeOrderStatus($request->orderId, oStatus::SEARCHING_FOR_DRIVER); // back again the order to its default status
        } else if ($request->status == oStatus::COMPLETED->value) {
            Order::changeOrderStatus($request->orderId, $request->status);
            return redirect()->route('history');
        } else  {
            Order::changeOrderStatus($request->orderId, $request->status);
        }
        event(new DriverNotification($request->userId, $request->orderId,getOrderMessage($request->status)));
        return  redirect()->route('track-order-view', ['id' => $request['orderId']])->with('success', 'status has changed successfully');
    }
    public function liveShare(Request $request)
    {
            SendLocation::dispatch(
                $request->input('lat'),
                $request->input('long'),
                $request->input('userId'),
                $request->input('orderId'),
            );
    }
    public function viewLaundry($laundryID)
    {
        $laundry = Laundry::all()->where('id', $laundryID)->first();
        return view('Driver.Order.view-laundry', ['laundry' => $laundry]);
    }
    public function history()
    {
        $order = Order::all()->where('status_id', oStatus::COMPLETED->value);
        return view('Driver.Order.history', ['order' => $order]);
    }


    public function activateLiveLocation(Request $request)
    {
        LiveShare::updateOrCreate(
            ['driver_id' => $request->driver_id],
            ['latitude' => $request->latitude, 'longitude' => $request->longitude],
        );
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
}
