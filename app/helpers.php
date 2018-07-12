<?php

if (!function_exists('object_to_array')) {
    /**
     * Convert stdClass object to array.
     *
     * @param object|array $data
     * @return array
     */
    function object_to_array($data)
    {
        if (is_object($data)) {
            // Gets the properties of the given object.
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            /*
            * Return array converted to object.
            * Using __FUNCTION__ (Magic constant) for recursive call.
            */
            return array_map(__FUNCTION__, $data);
        } else {
            // Return array.
            return $data;
        }
    }
}
