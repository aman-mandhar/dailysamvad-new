<?php

namespace App\Import\Support;

class MojibakeRepair
{
    public function repair(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $current = $value;

        for ($pass = 0; $pass < 3; $pass++) {
            $candidate = collect(['Windows-1252', 'ISO-8859-1'])
                ->map(fn (string $encoding): string => mb_convert_encoding($current, $encoding, 'UTF-8'))
                ->filter(fn (string $value): bool => mb_check_encoding($value, 'UTF-8'))
                ->sortBy(fn (string $value): int => $this->score($value))
                ->first();

            if (! is_string($candidate) || $this->score($candidate) >= $this->score($current)) {
                break;
            }

            $current = $candidate;
        }

        return $current;
    }

    private function score(string $value): int
    {
        $doubleEncoded = preg_match_all('/[\x{00C2}\x{00C3}]/u', $value);
        $singleEncoded = preg_match_all('/\x{00E0}[\x{00A4}\x{00A5}]/u', $value);

        return ($doubleEncoded * 10) + ($singleEncoded * 5);
    }
}
