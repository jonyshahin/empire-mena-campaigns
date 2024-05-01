<?php

namespace App\Http\Controllers;

use App\Models\RefusedReason;
use Illuminate\Http\Request;

class RefusedReasonController extends Controller
{
    // Display a listing of reasons
    public function index()
    {
        $reasons = RefusedReason::all();
        return view('refused_reasons.index', compact('reasons'));
    }

    // Show the form for creating a new reason
    public function create()
    {
        return view('refused_reasons.create');
    }

    // Store a newly created reason in storage
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|integer',
        ]);

        RefusedReason::create($request->all());
        return redirect()->route('refusedreason')->with('success', 'Reason for refusal added successfully!');
    }
}
