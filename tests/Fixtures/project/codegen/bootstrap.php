<?php

declare(strict_types=1);

// Loaded through the "bootstrap" option; guarded because tests run many generations per process.
if (! class_exists('Fixture\\Codegen\\ModesExtension', false)) {
    require __DIR__ . '/classes.php';
}
