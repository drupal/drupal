<?php

declare(strict_types=1);

namespace Drupal\package_manager_bypass;

use Composer\Json\JsonFile;
use Drupal\Core\State\StateInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * A composer-stager Stager implementation that does nothing, except logging.
 *
 * By default, it will modify composer.lock in the stage directory, to fool the
 * \Drupal\package_manager\Validator\LockFileValidator into thinking that there
 * are pending composer operations.
 *
 * Opt out of this by calling @code setLockFileShouldChange(FALSE) @endcode.
 *
 * @see ::setLockFileShouldChange()
 * @see \Drupal\package_manager\Validator\LockFileValidator
 *
 * @internal
 */
final class NoOpStager implements StagerInterface {

  use ComposerStagerExceptionTrait;
  use LoggingDecoratorTrait;

  /**
   * Constructs a Stager object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function stage(array $composerCommand, PathInterface $activeDir, PathInterface $stagingDir, ?OutputCallbackInterface $callback = NULL, ?int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    $this->saveInvocationArguments($composerCommand, $stagingDir, $timeout);
    $this->throwExceptionIfSet();

    // If desired, simulate a change to the lock file (e.g., as a result of
    // running `composer update`).
    $lockFile = new JsonFile($stagingDir->absolute() . '/composer.lock');
    $changeLockFile = $this->state->get(static::class . ' lock', TRUE);

    if ($changeLockFile && $lockFile->exists()) {
      $data = $lockFile->read();
      $data['_time'] = microtime();
      $lockFile->write($data);
    }
  }

  /**
   * Sets whether ::stage() should simulate a change in the lock file.
   *
   * @param bool $value
   *   (optional) Whether to simulate a change in the lock file when
   *   ::stage() is called. Defaults to TRUE.
   */
  public static function setLockFileShouldChange(bool $value = TRUE): void {
    \Drupal::state()->set(static::class . ' lock', $value);
  }

}
