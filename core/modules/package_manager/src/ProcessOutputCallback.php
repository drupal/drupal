<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * A process callback for capturing output.
 *
 * @see \Symfony\Component\Process\Process
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ProcessOutputCallback implements OutputCallbackInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The output buffer.
   *
   * @var array
   */
  private array $outBuffer = [];

  /**
   * The error buffer.
   *
   * @var array
   */
  private array $errorBuffer = [];

  /**
   * Constructs a ProcessOutputCallback object.
   */
  public function __construct() {
    $this->setLogger(new NullLogger());
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(OutputTypeEnum $type, string $buffer): void {

    if ($type === OutputTypeEnum::OUT) {
      $this->outBuffer[] = $buffer;
    }
    elseif ($type === OutputTypeEnum::ERR) {
      $this->errorBuffer[] = $buffer;
    }
  }

  /**
   * Gets the output.
   *
   * If there is anything in the error buffer, it will be logged as a warning.
   *
   * @return array
   *   The output buffer.
   */
  public function getOutput(): array {
    $error_output = $this->getErrorOutput();
    if ($error_output) {
      $this->logger->warning(implode('', $error_output));
    }
    return $this->outBuffer;
  }

  /**
   * Gets the parsed JSON output.
   *
   * @return mixed
   *   The decoded JSON output or NULL if there isn't any.
   */
  public function parseJsonOutput(): mixed {
    $output = $this->getOutput();
    if ($output) {
      return json_decode(trim(implode('', $output)), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    return NULL;
  }

  /**
   * Gets the error output.
   *
   * @return array
   *   The error output buffer.
   */
  public function getErrorOutput(): array {
    return $this->errorBuffer;
  }

  /**
   * {@inheritdoc}
   */
  public function clearErrorOutput(): void {
    $this->errorBuffer = [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearOutput(): void {
    $this->outBuffer = [];
  }

  /**
   * Resets buffers.
   *
   * @return self
   */
  public function reset(): self {
    $this->clearErrorOutput();
    $this->clearOutput();
    return $this;
  }

}
