<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client as HttpClient;
use Carbon\Carbon;
use Dingo\Api\Exception\ValidationHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WeatherController extends Controller
{
    private $darkSky;
    private $google;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->darkSky = new HttpClient(['base_uri' => env('DARK_SKY_ENDPOINT').env('DARK_SKY_SECRET_KEY').'/', 'http_errors' => false]);
        $this->google = new HttpClient(['base_uri' => env('GOOGLE_GEOCODING_ENDPOINT'), 'http_errors' => false]);
    }

    public function weather(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'bail|required|numeric',
            'longitude' => 'bail|required|numeric',
            'datetime' => 'string',
            'timestamp' => 'integer',
            'language' => 'string',
            'geocoding' => 'boolean',
            'only' => 'string',
            'except' => 'string',
        ]);

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors());
        }

        $result = Cache::get('weather:request:'.serialize($request->except(['only', 'except'])));

        if (!$result) {
            if ($request->filled('datetime')) {
                try {
                    $carbon = new Carbon($request->datetime);
                    $time = $carbon->timestamp;
                } catch (\Exception $exp) {
                    throw new BadRequestHttpException($exp->getMessage());
                }
            } elseif ($request->filled('timestamp')) {
                $time = $request->timestamp;
            }

            $darkSkyPath = isset($time) ? $request->latitude.','.$request->longitude.','.$time : $request->latitude.','.$request->longitude;

            $forecast = $this->darkSky->get($darkSkyPath, [
                'query' => [
                    'lang' => $request->filled('language') ? $request->language : null,
                    'units' => 'ca',
                    'exclude' => 'minutely, hourly, daily, alerts, flags',
                ],
            ]);
            $forecastBody = json_decode($forecast->getBody());

            if ($forecast->getStatusCode() !== 200) {
                throw new HttpException($forecastBody->code, $forecastBody->error);
            }

            $weather = $forecastBody->currently;

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

            // add geocoding if requested
            if ($request->input('geocoding', false)) {
                $geocoding = $this->google->get('json', [
                    'query' => [
                        'latlng' => $request->latitude.','.$request->longitude,
                        'key' => env('GOOGLE_GEOCODING_API_KEY'),
                    ],
                ]);

                $geocodingBody = json_decode($geocoding->getBody());

                $result['geocode'] = head($geocodingBody->results);
            }

            Cache::put('weather:request:'.serialize($request->except(['only', 'except'])), $result, env('WEATHER_CACHE_MINUTES', 5));
        }

        $response = Response::ONE($request, $result, 'nested');

        return response()->json($response);
    }
}
