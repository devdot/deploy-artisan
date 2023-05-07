<?php

namespace Devdot\DeployArtisan\Contracts;

interface DeployCommand
{
    public function handle(): int;

    public function preRunComment(): string;

    public function postRunComment(): string;
}
