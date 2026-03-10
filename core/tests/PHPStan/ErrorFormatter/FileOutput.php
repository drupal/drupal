<?php

declare(strict_types=1);

namespace Drupal\PHPStan\ErrorFormatter;

use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;

/**
 * Output implementation that writes to a file.
 *
 * @phpstan-ignore phpstanApi.interface
 */
final class FileOutput implements Output {

  /**
   * The file handle.
   */
  private \SplFileObject $handle;

  /**
   * Constructs a FileOutput.
   *
   * @param string $filePath
   *   The path to the output file.
   * @param \PHPStan\Command\OutputStyle $outputStyle
   *   The output style.
   */
  public function __construct(string $filePath, private OutputStyle $outputStyle) {
    $directory = dirname($filePath);
    if ($directory && $directory !== 'php:' && !is_dir($directory)) {
      mkdir($directory, 0777, TRUE);
    }
    $this->handle = new \SplFileObject($filePath, 'w');
  }

  /**
   * {@inheritdoc}
   */
  public function writeFormatted(string $message): void {
    $this->handle->fwrite($message);
  }

  /**
   * {@inheritdoc}
   */
  public function writeLineFormatted(string $message): void {
    $this->handle->fwrite($message . "\n");
  }

  /**
   * {@inheritdoc}
   */
  public function writeRaw(string $message): void {
    $this->handle->fwrite($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle(): OutputStyle {
    return $this->outputStyle;
  }

  /**
   * {@inheritdoc}
   */
  public function isVerbose(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isVeryVerbose(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDebug(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDecorated(): bool {
    return FALSE;
  }

}
