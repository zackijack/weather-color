<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index()
    {
        $response = [
            'status' => 'succeed',
        ];

        return $response;
    }
}
