<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RefusedReason;
use Illuminate\Http\Request;

class RefusedReasonController extends Controller
{
    public function index()
    {
        $refusedReasons = RefusedReason::all();
        return custom_success(200, 'Refused Reasons', $refusedReasons);
    }
}
