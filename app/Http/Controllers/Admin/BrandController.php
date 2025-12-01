<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BrandService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandController extends Controller
{
    protected BrandService $service;

    public function __construct(BrandService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $brands = $this->service->searchForAdmin($request->search ?? '');

        return Inertia::render('Admin/Brand/Index', [
            'brands' => $brands,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        $this->service->createBrand($request->all());

        return redirect()->back()->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,'.$id,
        ]);

        $this->service->updateBrand((int)$id, $request->all());

        return redirect()->back()->with('success', 'Brand updated successfully.');
    }

    public function destroy(string $id)
    {
        $this->service->deleteBrand((int)$id);

        return redirect()->back()->with('success', 'Brand deleted successfully.');
    }
}