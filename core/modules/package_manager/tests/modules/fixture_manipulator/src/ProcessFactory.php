<?php

declare(strict_types=1);

namespace Drupal\fixture_manipulator;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * Process factory that always sets the COMPOSER_MIRROR_PATH_REPOS env variable.
 *
 * This is necessary because the fake_site fixture is built from a Composer-type
 * repository, which will normally try to symlink packages which are installed
 * from local directories, which in turn will break Package Manager because it
 * does not support symlinks pointing outside the main code base. The
 * COMPOSER_MIRROR_PATH_REPOS environment variable forces Composer to mirror,
 * rather than symlink, local directories when installing packages.
 *
 * @see \Drupal\fixture_manipulator\FixtureManipulator::setUpRepos()
 */
final class ProcessFactory implements ProcessFactoryInterface {

  /**
   * Constructs a ProcessFactory object.
   *
   * @param \PhpTuf\ComposerStager\API\Process\Factory\ProcessFactoryInterface $decorated
   *   The decorated process factory service.
   */
  public function __construct(private readonly ProcessFactoryInterface $decorated) {}

  /**
   * {@inheritdoc}
   */
  public function create(array $command, ?PathInterface $cwd = NULL, array $env = []): ProcessInterface {
    $process = $this->decorated->create($command, $cwd, $env);

    $env = $process->getEnv();
    $env['COMPOSER_MIRROR_PATH_REPOS'] = '1';
    $process->setEnv($env);
    return $process;
  }

}
