<?php

namespace App\Services;

use App\Models\DataProvider;
use App\Models\Location;

class ModelValidator
{
    public function isValid(string $className, object $obj)
    {
        $valid = false;
        switch ($className)
        {
            case Location::class:
                if (
                    is_numeric($obj->lat) &&
                    $obj->lat >= -90 &&
                    $obj->lat <= 90 &&
                    is_numeric($obj->lon) &&
                    $obj->lon >= -180 &&
                    $obj->lon <= 180
                ) {
                    $valid = true;
                }
                break;
            case DataProvider::class:
                if (
                    !empty($obj->name) &&
                    !empty($obj->url) &&
                    filter_var($obj->url, FILTER_VALIDATE_URL) &&
                    !empty($obj->lat_lon_format)
                ) {
                    $valid = true;
                }
                break;
            default: $valid = false;
        }
        return $valid;
    }
}