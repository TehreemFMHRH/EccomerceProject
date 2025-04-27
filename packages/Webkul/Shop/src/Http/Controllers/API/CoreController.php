<?php

namespace Webkul\Shop\Http\Controllers\API;

class CoreController extends APIController
{
    
    public function getCountries()
    {
        return response()->json([
            'data' => core()->countries()->map(fn ($country) => [
                'id'   => $country->id,
                'code' => $country->code,
                'name' => $country->name,
            ]),
        ]);
    }

    
    public function getStates()
    {
        return response()->json([
            'data' => core()->groupedStatesByCountries(),
        ]);
    }
}
