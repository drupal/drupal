<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * Logs Composer Stager's Stager process output to a file.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class LoggingStager implements StagerInterface {

  public function __construct(
    private readonly StagerInterface $decorated,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function stage(array $composerCommand, PathInterface $activeDir, PathInterface $stagingDir, ?OutputCallbackInterface $callback = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    $path = $this->configFactory->get('package_manager.settings')->get('log');
    if ($path) {
      $callback = new FileProcessOutputCallback($path, $callback);
      $callback(OutputTypeEnum::OUT, sprintf("### Staging '%s' in %s\n", implode(' ', $composerCommand), $stagingDir->absolute()));
    }
    $this->decorated->stage($composerCommand, $activeDir, $stagingDir, $callback, $timeout);
  }

}
