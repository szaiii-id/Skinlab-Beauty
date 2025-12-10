<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The order ID instance.
     * We pass ID instead of Model to prevent serialization issues if data changes.
     */
    protected int $orderId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Retrieve the Order from Database
        $order = Order::find($this->orderId);

        // Safety check: If order was deleted before job ran
        if (!$order) {
            Log::warning("ProcessOrder Job Skipped: Order ID {$this->orderId} not found.");
            return;
        }

        Log::info("=== JOB STARTED: Processing Order #{$order->order_number} ===");

        try {
            // --- SIMULATION OF HEAVY TASKS ---
            
            // Task A: Generate PDF Invoice (Simulated 2 seconds)
            // In real app: PdfService::generate($order);
            Log::info("Step 1: Generating PDF Invoice...");
            sleep(2); 
            
            // Task B: Send Email Notification to Courier/Warehouse (Simulated 2 seconds)
            // In real app: Mail::to('warehouse@skinlab.com')->send(new ShippingOrderMail($order));
            Log::info("Step 2: Sending notification email to Warehouse...");
            sleep(2);

            // Task C: Sync Stock with External ERP/System (Simulated 1 second)
            // In real app: ErpService::syncInventory($order);
            Log::info("Step 3: Syncing stock with external ERP...");
            sleep(1);

            // --- JOB FINISHED ---
            Log::info("=== JOB COMPLETED: Order #{$order->order_number} processed successfully. ===");

        } catch (\Exception $e) {
            // Error Handling
            Log::error("=== JOB FAILED: Order #{$order->order_number} ===");
            Log::error("Reason: " . $e->getMessage());

            // Optional: Release the job back to queue to retry after 10 seconds
            // $this->release(10);
            
            // Or explicitly mark as failed
            $this->fail($e);
        }
    }
}