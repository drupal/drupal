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
   *
   * @var resource
   */
  private $handle;

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

    $handle = fopen($filePath, 'w');
    if ($handle === FALSE) {
      throw new \RuntimeException(sprintf('Unable to open file for writing: %s', $filePath));
    }
    $this->handle = $handle;
  }

  /**
   * Ensures the file is closed on destruction.
   */
  public function __destruct() {
    fclose($this->handle);
  }

  /**
   * {@inheritdoc}
   */
  public function writeFormatted(string $message): void {
    fwrite($this->handle, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function writeLineFormatted(string $message): void {
    fwrite($this->handle, $message . "\n");
  }

  /**
   * {@inheritdoc}
   */
  public function writeRaw(string $message): void {
    fwrite($this->handle, $message);
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
