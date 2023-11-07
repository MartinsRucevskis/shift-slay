<?php

namespace App\Shift\Shifter;

class DepreciatedPackages
{
    public function __construct(private string $package)
    {
        preg_match('#([A-Za-z\\\\]+)(?=::|$)#ms', $this->package, $matches);
        $this->package = $matches[1] ?? $this->package;
    }

    public function isDepreciated(): bool
    {
        return in_array($this->package, $this->depreciatedPackaged());
    }

    private function depreciatedPackaged(): array
    {
        $command = config('shift.command_name');
        $command = explode(':', $command);
        $command = $command[1];

        return match ($command) {
            'Lumen8ToLaravel8' => [
                'Pearl\RequestValidate\RequestServiceProvider',
            ],
            default => []
        };
    }
}
