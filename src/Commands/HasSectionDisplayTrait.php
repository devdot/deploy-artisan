<?php

namespace Devdot\DeployArtisan\Commands;

/** */
trait HasSectionDisplayTrait
{
    /**
     * Print a section component
     * @param array<string, string> $data
     */
    protected function displaySection(string $section, array $data): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('  <fg=green;options=bold>' . $section . '</>');
        foreach ($data as $key => $value) {
            $this->components->twoColumnDetail($key, $value);
        }
    }
}
