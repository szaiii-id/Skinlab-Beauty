<?php
// app/Http\Controllers\Admin\BanRequestController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BanRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BanRequestController extends Controller
{
    /**
     * Display ban requests
     */
    public function index(Request $request)
    {
        $currentAdmin = Auth::guard('admin')->user();
        $status = $request->query('status', 'pending');
        
        // Build query
        $query = BanRequest::with(['user', 'requester', 'reviewer'])
            ->latest();
        
        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // If not super admin, only show their requests
        if (!$currentAdmin->isSuperAdmin()) {
            $query->where('requested_by', $currentAdmin->id);
        }
        
        $requests = $query->paginate(20)->withQueryString();
        
        return Inertia::render('Admin/BanRequests/Index', [
            'requests' => $requests,
            'filters' => ['status' => $status],
            'currentAdmin' => [
                'id' => $currentAdmin->id,
                'name' => $currentAdmin->name,
                'role' => $currentAdmin->role,
                'is_super_admin' => $currentAdmin->isSuperAdmin()
            ]
        ]);
    }
    

    
    /**
     * Approve ban request (super admin only)
     */
    public function approve(Request $request, $id)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$currentAdmin->isSuperAdmin()) {
            return back()->with('error', 'Only super admin can approve ban requests.');
        }
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);
        
        $banRequest = BanRequest::with('user')->findOrFail($id);
        
        if ($banRequest->status !== BanRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been processed.');
        }
        
        DB::beginTransaction();
        try {
            // Ban the user
            $banRequest->user->ban($banRequest->reason, $currentAdmin);
            
            $banRequest->user->notify(new \App\Notifications\AccountBanned(
                $banRequest->reason, 
                $banRequest->description // Sertakan deskripsi asli dari request admin
            ));
            // Update ban request
            $banRequest->update([
                'status' => BanRequest::STATUS_APPROVED,
                'reviewed_by' => $currentAdmin->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['notes'] ?? null
            ]);
            
            DB::commit();
            
            Log::channel('admin_actions')->info('Ban request approved', [
                'admin_id' => $currentAdmin->id,
                'admin_name' => $currentAdmin->name,
                'request_id' => $id,
                'user_id' => $banRequest->user_id
            ]);
            
            return back()->with('success', 'Ban request approved successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve ban request failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to approve ban request: ' . $e->getMessage());
        }
    }
    
    /**
     * Reject ban request (super admin only)
     */
    public function reject(Request $request, $id)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$currentAdmin->isSuperAdmin()) {
            return back()->with('error', 'Only super admin can reject ban requests.');
        }
        
        $validated = $request->validate([
            'notes' => 'required|string|min:10|max:500'
        ]);
        
        $banRequest = BanRequest::findOrFail($id);
        
        if ($banRequest->status !== BanRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been processed.');
        }
        
        try {
            $banRequest->update([
                'status' => BanRequest::STATUS_REJECTED,
                'reviewed_by' => $currentAdmin->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['notes']
            ]);
            
            Log::channel('admin_actions')->info('Ban request rejected', [
                'admin_id' => $currentAdmin->id,
                'admin_name' => $currentAdmin->name,
                'request_id' => $id,
                'user_id' => $banRequest->user_id
            ]);
            
            return back()->with('success', 'Ban request rejected.');
            
        } catch (\Exception $e) {
            Log::error('Reject ban request failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to reject ban request: ' . $e->getMessage());
        }
    }
}