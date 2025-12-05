<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\BrandService;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    protected $productService;
    protected $brandService;
    protected $categoryService;

    public function __construct(
        ProductService $productService,
        BrandService $brandService,
        CategoryService $categoryService
    ) {
        $this->productService = $productService;
        $this->brandService = $brandService;
        $this->categoryService = $categoryService;
    }

    /**
     * Tampilkan Daftar Produk
     */
    public function index(Request $request)
    {
        $products = $this->productService->searchForAdmin($request->search ?? '');

        return Inertia::render('Admin/Product/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Tampilkan Form Tambah Produk
     */
    public function create()
    {
        return Inertia::render('Admin/Product/Create', [
            // Kirim data untuk Dropdown
            'brands' => $this->brandService->getAllBrands(), // Pastikan method ini me-return Collection, bukan Paginator
            'categories' => $this->categoryService->getAllCategories(),
            
            // Kirim opsi Tags standar (Sesuai diskusi Poin 3)
            'skinTypes' => ['Oily', 'Dry', 'Combination', 'Sensitive', 'Normal'],
            'skinConcerns' => ['Acne', 'Aging', 'Dullness', 'Dark Spots', 'Pores', 'Redness'],
        ]);
    }

    /**
     * Proses Simpan Data
     */
    public function store(Request $request)
    {
        // Validasi Kompleks untuk Nested Data (Varian)
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'thumbnail' => 'nullable|image|max:2048', // Max 2MB
            'suitability_tags' => 'nullable|array',
            
            // Validasi Array Varian
            'variants' => 'required|array|min:1',
            'variants.*.volume' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string', // Boleh kosong (Auto-gen)
            'variants.*.image_file' => 'nullable|image|max:2048',
        ]);

        // Panggil Service
        $thumbnail = $request->file('thumbnail');
        // Pisahkan data varian dari data induk
        $variants = $data['variants'];
        // Masukkan file gambar varian ke array variants (karena validasi memisahkan file dari array request kadang)
        foreach ($request->variants as $index => $variantData) {
            if (isset($variantData['image_file'])) {
                $variants[$index]['image_file'] = $variantData['image_file'];
            }
        }
        
        // Buang 'variants' dari data induk agar tidak error saat create Product
        unset($data['variants']); 

        $this->productService->createProduct($data, $thumbnail, $variants);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Tampilkan Form Edit
     */
    public function edit(string $id)
    {
        // Ambil produk beserta relasinya
        $product = \App\Models\Product::with(['variants', 'brand', 'category'])->findOrFail($id);

        return Inertia::render('Admin/Product/Create', [
            'product' => $product,
            'brands' => $this->brandService->getAllBrands(),
            'categories' => $this->categoryService->getAllCategories(),
            'skinTypes' => ['Oily', 'Dry', 'Combination', 'Sensitive', 'Normal'],
            'skinConcerns' => ['Acne', 'Aging', 'Dullness', 'Dark Spots', 'Pores', 'Redness'],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'thumbnail' => 'nullable|image|max:2048', 
            'suitability_tags' => 'nullable|array',
            
            'variants' => 'required|array|min:1',
            
            // --- TAMBAHAN WAJIB (AGAR ID TIDAK HILANG) ---
            'variants.*.id' => 'nullable|integer', 
            // ---------------------------------------------
            
            'variants.*.volume' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string',
            'variants.*.image_file' => 'nullable|image|max:2048',
            'variants.*.image_url' => 'nullable|string',
        ]);
        
        $product = Product::findOrFail($id);
        $thumbnail = $request->file('thumbnail');
        
        // Ambil variants dari $data (yang sekarang SUDAH ADA ID-nya)
        $variants = $data['variants']; 
        
        // Mapping file gambar varian (tetap sama)
        foreach ($request->variants as $index => $variantData) {
            if (isset($variantData['image_file'])) {
                $variants[$index]['image_file'] = $variantData['image_file'];
            }
        }

        unset($data['variants']); 

        $this->productService->updateProduct($product->id, $data, $thumbnail, $variants);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Hapus Produk
     */
    public function destroy(string $id)
    {
        // Service akan otomatis hapus varian juga (Cascade DB)
        $this->productService->deleteProduct((int)$id);

        return redirect()->back()->with('success', 'Product deleted.');
    }
}