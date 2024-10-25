<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * Logs Composer Stager's Beginner process output to a file.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class LoggingBeginner implements BeginnerInterface {

  public function __construct(
    private readonly BeginnerInterface $decorated,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function begin(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, ?OutputCallbackInterface $callback = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    $path = $this->configFactory->get('package_manager.settings')->get('log');
    if ($path) {
      $callback = new FileProcessOutputCallback($path, $callback);
      $callback(OutputTypeEnum::OUT, sprintf("### Beginning in %s\n", $stagingDir->absolute()));
    }

    $start_time = $this->time->getCurrentMicroTime();
    $this->decorated->begin($activeDir, $stagingDir, $exclusions, $callback, $timeout);
    $end_time = $this->time->getCurrentMicroTime();
    if ($callback) {
      $callback(OutputTypeEnum::OUT, sprintf("### Finished in %0.3f seconds\n", $end_time - $start_time));
    }
  }

}
