<?php

namespace App\Http\Controllers;

use App\Models\CompetitorBrand;
use Illuminate\Http\Request;

class CompetitorBrandController extends Controller
{
    // Display a listing of competitor brands
    public function index()
    {
        $brands = CompetitorBrand::all();
        return view('competitor_brands.index', compact('brands'));
    }

    // Show the form for creating a new competitor brand
    public function create()
    {
        return view('competitor_brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        CompetitorBrand::create($request->all());
        return redirect()->route('competitor')->with('success', 'Competitor brand added successfully!');
    }
}
