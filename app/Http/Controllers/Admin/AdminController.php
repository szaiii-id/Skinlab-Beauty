<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * 1. LIST STAFF
     * Display a list of admins with search filters and sorting.
     */
    public function index(Request $request)
    {
        $query = Admin::query();

        // Search Feature (Name or Email)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        // Sorting: Super Admin first, then alphabetical by name
        $staffs = $query->orderByRaw("FIELD(role, 'super_admin') DESC")
                        ->orderBy('name')
                        ->get();

        return Inertia::render('Admin/Staff/Index', [
            'staffs' => $staffs,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * 2. CREATE FORM
     */
    public function create()
    {
        return Inertia::render('Admin/Staff/Form');
    }

    /**
     * 3. STORE NEW STAFF
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:admins,email',
            'role'     => 'required|in:super_admin,warehouse,marketing',
            'password' => 'required|min:6',
        ]);

        Admin::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.staff.index')
                         ->with('success', 'New staff member registered successfully.');
    }

    /**
     * 4. EDIT FORM
     */
    public function edit($id)
    {
        $staff = Admin::findOrFail($id);
        return Inertia::render('Admin/Staff/Form', ['staff' => $staff]);
    }

    /**
     * 5. UPDATE STAFF
     */
    public function update(Request $request, $id)
    {
        $staff = Admin::findOrFail($id);
        $currentUser = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            // Unique email validation, ignoring the current staff's ID
            'email' => ['required', 'email', Rule::unique('admins')->ignore($staff->id)],
            'role'  => 'required|in:super_admin,warehouse,marketing',
            'password' => 'nullable|min:6', // Password is optional during update
        ]);

        // SAFETY: Prevent user from changing their own role (Anti-Lockout)
        // e.g., A Super Admin accidentally demoting themselves to Warehouse
        if ($staff->id === $currentUser->id && $validated['role'] !== $staff->role) {
            return back()->withErrors(['role' => 'For security reasons, you cannot change your own role.']);
        }

        $dataToUpdate = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ];

        // Only hash and update password if a new one is provided
        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($validated['password']);
        }

        $staff->update($dataToUpdate);

        return redirect()->route('admin.staff.index')
                         ->with('success', 'Staff profile updated successfully.');
    }

    /**
     * 6. TOGGLE STATUS (Suspend/Activate)
     * Replaces delete for temporary leave or suspension.
     */
    public function toggleStatus($id)
    {
        $staff = Admin::findOrFail($id);
        $currentUser = Auth::guard('admin')->user();

        // SAFETY: Prevent deactivating own account
        if ($staff->id === $currentUser->id) {
            return back()->withErrors('You cannot deactivate your own account.');
        }

        // Toggle status
        $staff->is_active = !$staff->is_active;
        $staff->save();

        $status = $staff->is_active ? 'activated' : 'suspended';
        return back()->with('success', "Account for {$staff->name} has been {$status}.");
    }

    /**
     * 7. DELETE (Soft Delete)
     * For removing incorrect data or permanently leaving staff.
     */
    public function destroy($id)
    {
        $staff = Admin::findOrFail($id);
        $currentUser = Auth::guard('admin')->user();

        // SAFETY: Prevent deleting own account
        if ($staff->id === $currentUser->id) {
            return back()->withErrors('You CANNOT delete the account you are currently using.');
        }

        $staff->delete();

        return back()->with('success', 'Staff account deleted successfully.');
    }
}