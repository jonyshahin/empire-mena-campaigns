<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CompetitorBrand;
use Illuminate\Http\Request;

class CompetitorBrandController extends Controller
{
    public function index()
    {
        $competitorBrands = CompetitorBrand::all();
        return custom_success(200, 'CompetitorBrand List', $competitorBrands);
    }
}
