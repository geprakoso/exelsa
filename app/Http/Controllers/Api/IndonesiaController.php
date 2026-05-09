<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;

class IndonesiaController extends Controller
{
    public function provinces()
    {
        $provinces = Province::query()
            ->orderBy('name')
            ->get(['code', 'name']);

        return response()->json($provinces);
    }

    public function cities(Request $request)
    {
        $request->validate([
            'province_code' => 'required|string',
        ]);

        $cities = City::query()
            ->where('province_code', $request->province_code)
            ->orderBy('name')
            ->get(['code', 'name']);

        return response()->json($cities);
    }

    public function districts(Request $request)
    {
        $request->validate([
            'city_code' => 'required|string',
        ]);

        $districts = District::query()
            ->where('city_code', $request->city_code)
            ->orderBy('name')
            ->get(['code', 'name']);

        return response()->json($districts);
    }
}