<?php

declare(strict_types=1);

// Laravel's translation helper, used by the generated labels (see the "translate" option).
if (! function_exists('__')) {
    function __(string $key): string
    {
        return $key;
    }
}
