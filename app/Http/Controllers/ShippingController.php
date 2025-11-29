<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAddress;
use App\Models\Order;
use App\Services\ShippingService;

class ShippingController extends Controller
{
    protected ShippingService $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Check Shipping Rates
     */
    public function checkRates(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:user_addresses,id',
            'items'      => 'required|array'
        ]);

        try {
            $address = UserAddress::findOrFail($request->address_id);
            
            if (empty($address->komerce_destination_id)) {
                return response()->json(['message' => 'Address not verified. Please edit address.'], 400);
            }

            $rates = $this->shippingService->calculateRates($address, $request->items);

            return response()->json(['rates' => $rates]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Request Pickup (Booking)
     */
    public function requestPickup($orderId)
    {
        try {
            $order = Order::with(['shippingAddress', 'items.productVariant'])
                ->where('id', $orderId)
                ->orWhere('order_number', $orderId)
                ->firstOrFail();

            $data = $this->shippingService->bookShipment($order);

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking Successful! Tracking Number: ' . $data['order_no'],
                'data'    => $data
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Print Shipping Label
     */
    public function getLabel($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $url = $this->shippingService->generateLabel($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Label generated',
                'url'    => $url
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Track Shipment
     */
    public function trackShipment($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $data = $this->shippingService->trackShipment($order);

            return response()->json([
                'status' => 'success',
                'data'   => $data
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel Shipment
     */
    public function cancelShipment($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $this->shippingService->cancelBooking($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Shipment canceled successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Order Detail (From Komerce)
     */
    public function getOrderDetail($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $data = $this->shippingService->getOrderDetail($order);

            return response()->json([
                'status' => 'success',
                'local_status' => $order->order_status,
                'komerce_data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Schedule Pickup
     */
    public function schedulePickup(Request $request)
    {
        $request->validate([
            'order_ids'      => 'required|array',
            'pickup_date'    => 'required|date_format:Y-m-d',
            'pickup_time'    => 'required',
            'pickup_vehicle' => 'required|in:Motor,Mobil,Truk'
        ]);

        try {
            $data = $this->shippingService->schedulePickup(
                $request->order_ids,
                $request->pickup_date,
                $request->pickup_time,
                $request->pickup_vehicle
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Pickup Scheduled Successfully!',
                'data'    => $data
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Webhook from Courier
     */
    public function handleWebhook(Request $request)
    {
        try {
            $this->shippingService->updateStatusFromWebhook($request->all());
            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    }
}