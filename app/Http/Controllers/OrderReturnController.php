<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderReturnRequest; // Gunakan Request khusus tadi
use App\Services\OrderReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class OrderReturnController extends Controller
{
    protected OrderReturnService $orderReturnService;

    public function __construct(OrderReturnService $orderReturnService)
    {
        $this->orderReturnService = $orderReturnService;
    }

    public function store(StoreOrderReturnRequest $request, $orderId): RedirectResponse
    {
        try {
            // Panggil Service untuk memproses logika
            // Kita kirim User, OrderID, Data Validasi, dan File Evidence
            $this->orderReturnService->createReturnRequest(
                Auth::user(),
                (int) $orderId,
                $request->validated(), // Hanya ambil data yang sudah lolos validasi
                $request->file('evidence')
            );

            // Gunakan 'toast_success' agar konsisten dengan Cart/Wishlist Controller
            return back()->with('toast_success', 'Return request submitted. Please wait for Admin verification.');

        } catch (\Exception $e) {
            // Tangkap error dari Service (misal: Order tidak ketemu, Status salah, dll)
            return back()->with('toast_error', $e->getMessage());
        }
    }
}