<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;

/**
 * Logs Composer Stager's Committer process output to a file.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class LoggingCommitter implements CommitterInterface {

  public function __construct(
    private readonly CommitterInterface $decorated,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function commit(PathInterface $stagingDir, PathInterface $activeDir, ?PathListInterface $exclusions = NULL, ?OutputCallbackInterface $callback = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    $path = $this->configFactory->get('package_manager.settings')->get('log');
    if ($path) {
      $callback = new FileProcessOutputCallback($path, $callback);
      $callback(OutputTypeEnum::OUT, sprintf("### Committing changes from %s to %s\n", $stagingDir->absolute(), $activeDir->absolute()));
    }

    $start_time = $this->time->getCurrentMicroTime();
    $this->decorated->commit($stagingDir, $activeDir, $exclusions, $callback, $timeout);
    $end_time = $this->time->getCurrentMicroTime();
    if ($callback) {
      $callback(OutputTypeEnum::OUT, sprintf("### Finished in %0.3f seconds\n", $end_time - $start_time));
    }
  }

}
