<?php

namespace App;

use Illuminate\Http\Request;

class Response
{
    public function __construct()
    {
        //
    }

    public static function ONE(Request $request, $response, string $type = 'assoc'): array
    {
        // Make sure the $response and everything nested in it is an array instead of an object.
        $array = object_to_array($response);

        switch ($type) {
            case 'assoc':
                if ($request->has('only')) {
                    $keys = explode(',', $request->only);
                    $filtered = self::assocArrayOnly($array, $keys);
                } elseif ($request->has('except')) {
                    $keys = explode(',', $request->except);
                    $filtered = self::assocArrayExcept($array, $keys);
                } else {
                    $filtered = $array;
                }
                break;

            case 'multi':
                if ($request->has('only')) {
                    $keys = explode(',', $request->only);
                    $filtered = self::multiArrayOnly($array, $keys);
                } elseif ($request->has('except')) {
                    $keys = explode(',', $request->except);
                    $filtered = self::multiArrayExcept($array, $keys);
                } else {
                    $filtered = $array;
                }
                break;

            case 'nested':
                if ($request->has('only')) {
                    $keys = explode(',', $request->only);
                    $filtered = self::nestedArrayOnly($array, $keys);
                } elseif ($request->has('except')) {
                    $keys = explode(',', $request->except);
                    $filtered = self::nestedArrayExcept($array, $keys);
                } else {
                    $filtered = $array;
                }
                break;

            default:
                $filtered = $array;
                break;
        }

        return $filtered;
    }

    public static function assocArray(array $array, array $keys, string $filter = 'only'): array
    {
        switch ($filter) {
            case 'only':
                $filtered = self::assocArrayOnly($array, $keys);
                break;

            case 'except':
                $filtered = self::assocArrayExcept($array, $keys);
                break;

            default:
                $filtered = $array;
                break;
        }

        return $filtered;
    }

    public static function assocArrayOnly(array $array, array $keys): array
    {
        return array_only($array, $keys);
    }

    public static function assocArrayExcept(array $array, array $keys): array
    {
        return array_except($array, $keys);
    }

    public static function multiArray(array $array, array $keys, $filter = 'only'): array
    {
        switch ($filter) {
            case 'only':
                $filtered = self::multiArrayOnly($array, $keys);
                break;

            case 'except':
                $filtered = self::multiArrayExcept($array, $keys);
                break;

            default:
                $filtered = $array;
                break;
        }

        return $filtered;
    }

    public static function multiArrayOnly(array $array, array $keys): array
    {
        $filtered = [];

        foreach ($array as $item) {
            $selected = array_only($item, $keys);
            array_push($filtered, $selected);
        }

        return $filtered;
    }

    public static function multiArrayExcept(array $array, array $keys): array
    {
        $filtered = [];

        foreach ($array as $item) {
            $selected = array_except($item, $keys);
            array_push($filtered, $selected);
        }

        return $filtered;
    }

    public static function nestedArray(array $array, array $keys, $filter = 'only'): array
    {
        switch ($filter) {
            case 'only':
                $filtered = $this->nestedArrayOnly($array, $keys);
                break;

            case 'except':
                $filtered = $this->nestedArrayExcept($array, $keys);
                break;

            default:
                $filtered = $array;
                break;
        }

        return $filtered;
    }

    public static function nestedArrayOnly(array $array, array $keys): array
    {
        $filtered = [];

        foreach ($keys as $key) {
            $value = array_get($array, $key);
            if ($value) {
                data_fill($filtered, $key, $value);
            }
        }

        return $filtered;
    }

    public static function nestedArrayExcept(array $array, array $keys): array
    {
        foreach ($keys as $key) {
            array_forget($array, $key);
        }

        return $array;
    }
}
