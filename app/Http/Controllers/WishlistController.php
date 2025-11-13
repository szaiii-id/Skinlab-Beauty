<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $wishlistItems = $user->wishlist()->with('product.brand','product.category')->get();
        
        return Inertia::render('Wishlist/Index', [
            'items' => $wishlistItems,
        ]);
    }

    public function store(Request $request, $variantId)
    {
        $user = Auth::user();

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            return redirect()->back()->with('toast_error', 'Product not found.');
        }

        $user->wishlist()->attach($variantId, false);
        return redirect()->back()->with('toast_success', 'Added to your Wishlist!');
    }

    public function destroy(Request $request,$variantId)
    {
        $user = Auth::user();

        $user->wishlist()->detach($variantId);
        return redirect()->back()->with('toast_success', 'Removed from your Wishlist!');
    }
}
