<?php

if (!function_exists('object_to_array')) {
    /**
     * Convert stdClass object to array
     *
     * @param object $object
     * @return array
     */
    function object_to_array($object)
    {
        if (is_object($object)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $object = get_object_vars($object);
        }

        if (is_array($object)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map(__FUNCTION__, $object);
        } else {
            // Return array
            return $object;
        }
    }
}
