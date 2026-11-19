<?php

declare(strict_types=1);

/**
 * Fails when Clover coverage is below the threshold for lines, methods or classes.
 *
 * Usage: php tools/coverage-check.php build/clover.xml [threshold=100]
 */
$file = $argv[1] ?? 'build/clover.xml';
$threshold = (float) ($argv[2] ?? 100);

if (! is_file($file)) {
    fwrite(STDERR, "Clover report not found: {$file}\n");
    exit(2);
}

$xml = simplexml_load_file($file);
if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Cannot parse Clover report: {$file}\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$classes = 0;
$coveredClasses = 0;

foreach ($xml->xpath('//class') ?: [] as $class) {
    $m = $class->metrics;
    ++$classes;
    if ((int) $m['methods'] === (int) $m['coveredmethods'] && (int) $m['statements'] === (int) $m['coveredstatements']) {
        ++$coveredClasses;
    }
}

$rows = [
    'Lines' => [(int) $metrics['coveredstatements'], (int) $metrics['statements']],
    'Methods' => [(int) $metrics['coveredmethods'], (int) $metrics['methods']],
    'Classes' => [$coveredClasses, $classes],
];

$failed = false;
foreach ($rows as $name => [$covered, $total]) {
    $percent = $total === 0 ? 100.0 : $covered / $total * 100;
    $ok = $percent + 1e-9 >= $threshold;
    $failed = $failed || ! $ok;
    printf("%-8s %6.2f%% (%d/%d)%s\n", $name, $percent, $covered, $total, $ok ? '' : "  < {$threshold}%");
}

if ($failed) {
    fwrite(STDERR, "Coverage is below {$threshold}%.\n");
    exit(1);
}
