<?php

namespace Devdot\DeployArtisan\Commands;

use Devdot\DeployArtisan\Models\System;

trait HasSystemDetectionTrait
{
    private System $detectedSystem;

    protected function getDetectedSystem(): System
    {
        if (!isset($this->detectedSystem)) {
            $this->detectedSystem = new System();
            $this->detectedSystem->detect();
        }
        return $this->detectedSystem;
    }
}
