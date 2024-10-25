<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

// cspell:ignore BINDIR

/**
 * Defines a process factory which sets the COMPOSER_HOME environment variable.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ProcessFactory implements ProcessFactoryInterface {

  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ProcessFactoryInterface $decorated,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function create(array $command, ?PathInterface $cwd = NULL, array $env = []): ProcessInterface {
    $process = $this->decorated->create($command, $cwd, $env);

    $env = $process->getEnv();
    if ($command && $this->isComposerCommand($command)) {
      $env['COMPOSER_HOME'] = $this->getComposerHomePath();
    }
    // Ensure that the current PHP installation is the first place that will be
    // searched when looking for the PHP interpreter.
    $env['PATH'] = static::getPhpDirectory() . ':' . getenv('PATH');
    $process->setEnv($env);
    return $process;
  }

  /**
   * Returns the directory which contains the PHP interpreter.
   *
   * @return string
   *   The path of the directory containing the PHP interpreter. If the server
   *   is running in a command-line interface, the directory portion of
   *   PHP_BINARY is returned; otherwise, the compile-time PHP_BINDIR is.
   *
   * @see php_sapi_name()
   * @see https://www.php.net/manual/en/reserved.constants.php
   */
  private static function getPhpDirectory(): string {
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
      return dirname(PHP_BINARY);
    }
    return PHP_BINDIR;
  }

  /**
   * Returns the path to use as the COMPOSER_HOME environment variable.
   *
   * @return string
   *   The path which should be used as COMPOSER_HOME.
   */
  private function getComposerHomePath(): string {
    $home_path = $this->fileSystem->getTempDirectory();
    $home_path .= '/package_manager_composer_home-';
    $home_path .= $this->configFactory->get('system.site')->get('uuid');
    $this->fileSystem->prepareDirectory($home_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    return $home_path;
  }

  /**
   * Determines if a command is running Composer.
   *
   * @param string[] $command
   *   The command parts.
   *
   * @return bool
   *   TRUE if the command is running Composer, FALSE otherwise.
   */
  private function isComposerCommand(array $command): bool {
    $executable = $command[0];
    $executable_parts = explode('/', $executable);
    $file = array_pop($executable_parts);
    return str_starts_with($file, 'composer');
  }

}
