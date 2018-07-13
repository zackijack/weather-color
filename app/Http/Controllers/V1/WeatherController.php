<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client as HttpClient;
use App\Response;

class WeatherController extends Controller
{
    private $http;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->http = new HttpClient(['base_uri' => env('DARK_SKY_ENDPOINT').env('DARK_SKY_SECRET_KEY').'/', 'http_errors' => false]);
    }

    public function forecast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'bail|required|numeric',
            'longitude' => 'bail|required|numeric',
            'only' => 'bail|string',
            'except' => 'bail|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $forecast = $this->http->get($request->latitude.','.$request->longitude, [
            'query' => [
                'units' => 'ca',
                'exclude' => 'minutely, hourly, daily, alerts, flags',
            ],
        ]);

        $weather = json_decode($forecast->getBody())->currently;

        $temperature = round($weather->temperature);

        $limit = env('WEATHER_TEMPERATURE_LIMIT');

        // make sure the temparature isn't above the limit
        $temperature = $temperature < $limit ? $temperature : $limit;

        // determine the hue
        $hue = 360 * ($temperature / $limit);

        $result = [
            'weather' => $weather,
            'color' => [
                'hue' => $hue,
            ],
        ];

        $response = Response::ONE($request, $result, 'nested');

        return response()->json($response);
    }
}
