<?php

namespace App\Services;

/**
 * Straight-line (great-circle) distance between two geocoded points, used to
 * show an approximate "how far away" figure. This is centroid-based, not
 * live GPS, so results are always presented to users as estimates.
 */
class DistanceEstimator
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function kilometersBetween(?float $originLatitude, ?float $originLongitude, ?float $targetLatitude, ?float $targetLongitude): ?float
    {
        if ($originLatitude === null || $originLongitude === null || $targetLatitude === null || $targetLongitude === null) {
            return null;
        }

        $latitudeDelta = deg2rad($targetLatitude - $originLatitude);
        $longitudeDelta = deg2rad($targetLongitude - $originLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($originLatitude)) * cos(deg2rad($targetLatitude)) * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
