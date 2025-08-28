#!/usr/bin/env php
<?php

/**
 * @file
 * PHPStan baseline statistics.
 */

if (PHP_SAPI !== 'cli') {
  return;
}

$args = $_SERVER['argv'];

if (!isset($args[1])) {
  echo "PHPStan baseline file path not specified.\n";
  exit(2);
}
$ignoreErrors = [];
require $args[1];

if (!isset($args[2])) {
  echo "Report output file path not specified.\n";
  exit(2);
}
$outputFilePath = $args[2];

$stats = ['__total' => 0];
foreach ($ignoreErrors as $ignore) {
  $identifier = $ignore['identifier'] ?? '* not specified *';
  $count = $ignore['count'] ?? 1;
  $stats['__total'] += $count;
  $stats[$identifier] = isset($stats[$identifier]) ? $stats[$identifier] + $count : $count;
}

echo "----------------------------------------\n";
echo "PHPStan baseline statistics\n";
echo "----------------------------------------\n";
echo sprintf("%6d * Total baselined errors\n", $stats['__total']);
echo "----------------------------------------\n";
echo "Breakdown by error identifier:\n";
file_put_contents(
  $outputFilePath,
  'phpstan-baseline ' . $stats['__total'] . \PHP_EOL,
  \FILE_APPEND,
);

unset($stats['__total']);
arsort($stats);

foreach ($stats as $identifier => $stat) {
  echo sprintf("%6d %s\n", $stat, $identifier);
  file_put_contents(
    $outputFilePath,
    'phpstan-baseline.' . $identifier . ' ' . $stat . \PHP_EOL,
    \FILE_APPEND
  );
}
