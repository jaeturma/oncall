<?php

namespace Tests\Unit;

use App\Services\DistanceEstimator;
use Tests\TestCase;

class DistanceEstimatorTest extends TestCase
{
    public function test_known_coordinate_pair_returns_the_expected_great_circle_distance(): void
    {
        $estimator = new DistanceEstimator;

        // Manila to Cebu City — well-known real-world distance, ~570km.
        $distance = $estimator->kilometersBetween(14.5995, 120.9842, 10.3157, 123.8854);

        $this->assertGreaterThan(560, $distance);
        $this->assertLessThan(580, $distance);
    }

    public function test_identical_coordinates_return_a_near_zero_distance(): void
    {
        $estimator = new DistanceEstimator;

        $distance = $estimator->kilometersBetween(10.0, 123.0, 10.0, 123.0);

        $this->assertEqualsWithDelta(0.0, $distance, 0.0001);
    }

    public function test_never_fabricates_a_distance_when_any_coordinate_is_missing(): void
    {
        $estimator = new DistanceEstimator;

        $this->assertNull($estimator->kilometersBetween(null, 123.0, 10.0, 123.0));
        $this->assertNull($estimator->kilometersBetween(10.0, null, 10.0, 123.0));
        $this->assertNull($estimator->kilometersBetween(10.0, 123.0, null, 123.0));
        $this->assertNull($estimator->kilometersBetween(10.0, 123.0, 10.0, null));
        $this->assertNull($estimator->kilometersBetween(null, null, null, null));
    }
}
