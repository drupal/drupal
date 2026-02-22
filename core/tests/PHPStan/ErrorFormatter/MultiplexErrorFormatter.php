<?php

declare(strict_types=1);

namespace Drupal\PHPStan\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\Container;

/**
 * Configurable error formatter that outputs to multiple formatters/files.
 */
final class MultiplexErrorFormatter implements ErrorFormatter {

  /**
   * Constructs the MultiplexErrorFormatter.
   *
   * @param \PHPStan\DependencyInjection\Container $container
   *   The service container.
   * @param string|false $basePath
   *   The base path to prepend to raw filenames, or FALSE for none.
   * @param array<string, string> $outputs
   *   Output filenames, keyed by formatter name.
   */
  public function __construct(
    private readonly Container $container,
    private readonly string|false $basePath = FALSE,
    private readonly array $outputs = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function formatErrors(AnalysisResult $analysisResult, Output $output): int {
    $exitCode = 0;

    foreach ($this->outputs as $formatterName => $outputPath) {
      if ($this->basePath && !str_contains($outputPath, '/')) {
        $outputPath = $this->basePath . '/' . $outputPath;
      }
      $formatter = $this->container->getService('errorFormatter.' . $formatterName);
      $formatterOutput = new FileOutput($outputPath, $output->getStyle());
      $result = $formatter->formatErrors($analysisResult, $formatterOutput);
      $exitCode = max($result, $exitCode);
    }

    return $exitCode;
  }

}
