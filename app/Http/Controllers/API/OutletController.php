<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = Outlet::all();
        return custom_success(200, 'Success', $outlets);
    }
}
