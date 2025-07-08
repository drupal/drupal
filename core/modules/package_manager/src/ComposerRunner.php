<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use Symfony\Component\Process\PhpExecutableFinder;

// cspell:ignore BINDIR

/**
 * Runs Composer through the current PHP interpreter.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ComposerRunner implements ComposerProcessRunnerInterface {

  public function __construct(
    private readonly ExecutableFinderInterface $executableFinder,
    private readonly ProcessFactoryInterface $processFactory,
    private readonly FileSystemInterface $fileSystem,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function run(array $command, ?PathInterface $cwd = NULL, array $env = [], ?OutputCallbackInterface $callback = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    // Run Composer through the PHP interpreter so we don't have to rely on
    // PHP being in the PATH.
    array_unshift($command, (new PhpExecutableFinder())->find(), $this->executableFinder->find('composer'));

    $home = $this->fileSystem->getTempDirectory();
    $home .= '/package_manager_composer_home-';
    $home .= $this->configFactory->get('system.site')->get('uuid');
    $this->fileSystem->prepareDirectory($home, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $process = $this->processFactory->create($command, $cwd, $env + ['COMPOSER_HOME' => $home]);
    $process->setTimeout($timeout);
    $process->mustRun($callback);
  }

}
