<?php

namespace App\Domain\AI\Contracts;

interface PredictionInterface
{
    /**
     * Predict a numerical score (0-100) based on features.
     */
    public function predictScore(array $features): int;

    /**
     * Forecast future time series values based on historical data.
     */
    public function predictTimeSeries(array $history, int $steps): array;
}
