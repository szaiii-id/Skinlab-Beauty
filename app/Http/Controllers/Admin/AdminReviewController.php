<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminReviewController extends Controller
{
    /**
     * Menampilkan daftar review dengan filter & search.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rating = $request->input('rating', 'all');

        $reviews = Review::with(['user', 'product']) // Eager load agar performa cepat
            ->when($search, function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$search}%"));
            })
            ->when($rating !== 'all', function ($q) use ($rating) {
                return $q->where('rating', $rating);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => [
                'search' => $search,
                'rating' => $rating
            ]
        ]);
    }

    /**
     * Sembunyikan/Tampilkan review (Moderasi).
     * Berguna jika ada review spam atau kata-kata kasar.
     */
    public function toggleHidden($id)
    {
        $review = Review::findOrFail($id);
        
        // Toggle nilai boolean (true <-> false)
        // Pastikan di database kolomnya 'is_hidden' (sesuai migrasi Anda)
        $review->update([
            'is_hidden' => !$review->is_hidden
        ]);

        $status = $review->is_hidden ? 'hidden' : 'visible';

        return back()->with('success', "Review is now {$status}.");
    }

    /**
     * Hapus permanen.
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return back()->with('success', 'Review deleted permanently.');
    }

    /**
     * Simpan balasan admin.
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string|max:1000'
        ]);

        $review = Review::findOrFail($id);

        $review->update([
            'admin_reply' => $request->reply,
            'reply_at' => now()
        ]);

        return back()->with('success', 'Balasan berhasil dikirim.');
    }
}