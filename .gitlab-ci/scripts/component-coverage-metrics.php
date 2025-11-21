#!/usr/bin/env php
<?php

/**
 * @file
 * Drupal's Component test coverage metrics.
 */

if (PHP_SAPI !== 'cli') {
  return;
}

$args = $_SERVER['argv'];

if (!isset($args[1])) {
  echo "Input coverage report file path not specified.\n";
  exit(2);
}
$reportFilePath = $args[1];

if (!isset($args[2])) {
  echo "Output metrics file path not specified.\n";
  exit(2);
}
$metricsFilePath = $args[2];

$report = @file_get_contents($reportFilePath);
if (empty($report)) {
  exit(0);
}

// Dump the report to STDOUT, with colors, for humans.
echo $report;

// Remove ANSI color codes and replace the file.
$output = preg_replace('/\x1b\[\d+(?:;\d+)*m/', '', $report);
file_put_contents($reportFilePath, $output);

// Find and report the metrics.
foreach (explode("\n", $output) as $line) {
  preg_match('/^\s*(Classes|Methods|Lines):\s+(\d+\.\d+%\s*\([\d\/\s]*\))\s*$/', $line, $m);
  if (!empty($m)) {
    $metric = strtolower($m[1]);
    $metricValue = str_replace(' ', '·', $m[2]);
    file_put_contents($metricsFilePath, "component.coverage.{$metric} {$metricValue}\n", \FILE_APPEND);
  }
}
