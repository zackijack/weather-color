<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client as HttpClient;
use Carbon\Carbon;
use Dingo\Api\Exception\ValidationHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

    public function weather(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'bail|required|numeric',
            'longitude' => 'bail|required|numeric',
            'datetime' => 'string',
            'timestamp' => 'integer',
            'language' => 'string',
            'only' => 'string',
            'except' => 'string',
        ]);

        if ($validator->fails()) {
            throw new ValidationHttpException($validator->errors());
        }

        if ($request->filled('datetime')) {
            try {
                $carbon = new Carbon($request->datetime);
                $time = $carbon->timestamp;
            } catch (\Exception $exp) {
                dd($exp);
                throw new BadRequestHttpException($exp->getMessage());
            }
        } elseif ($request->filled('timestamp')) {
            $time = $request->timestamp;
        }

        $path = isset($time) ? $request->latitude.','.$request->longitude.','.$time : $request->latitude.','.$request->longitude;

        $darkSky = $this->http->get($path, [
            'query' => [
                'lang' => $request->filled('language') ? $request->language : null,
                'units' => 'ca',
                'exclude' => 'minutely, hourly, daily, alerts, flags',
            ],
        ]);
        $body = json_decode($darkSky->getBody());

        if ($darkSky->getStatusCode() !== 200) {
            throw new HttpException($body->code, $body->error);
        }

        $weather = $body->currently;

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
