<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * Logs process output to a file.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class FileProcessOutputCallback implements OutputCallbackInterface {

  /**
   * The file to write to.
   *
   * @var resource
   */
  private readonly mixed $handle;

  public function __construct(
    string $path,
    private readonly ?OutputCallbackInterface $decorated = NULL,
  ) {
    $this->handle = fopen($path, 'a');
    if (empty($this->handle)) {
      throw new \RuntimeException("Could not open or create '$path' for writing.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearErrorOutput(): void {
    $this->decorated?->clearErrorOutput();
  }

  /**
   * {@inheritdoc}
   */
  public function clearOutput(): void {
    $this->decorated?->clearOutput();
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorOutput(): array {
    return $this->decorated?->getErrorOutput() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput(): array {
    return $this->decorated?->getOutput() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(OutputTypeEnum $type, string $buffer): void {
    fwrite($this->handle, $buffer);

    if ($this->decorated) {
      ($this->decorated)($type, $buffer);
    }
  }

}
