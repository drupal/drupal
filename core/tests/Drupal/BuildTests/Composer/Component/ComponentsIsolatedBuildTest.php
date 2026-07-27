<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Composer\Component;

use Drupal\BuildTests\Composer\ComposerBuildTestBase;
use Drupal\Composer\Composer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Try to install dependencies per component, using Composer.
 */
#[CoversNothing]
#[Group('Composer')]
#[Group('Component')]
class ComponentsIsolatedBuildTest extends ComposerBuildTestBase {

  /**
   * Test whether components' composer.json can be installed in isolation.
   */
  public function testComponentComposerJson(): void {
    // Only copy the components. Copy all of them because some of them depend on
    // each other.
    $finder = new Finder();
    $finder->files()
      ->ignoreUnreadableDirs()
      ->in($this->getDrupalRoot() . static::$componentsPath)
      ->ignoreDotFiles(FALSE)
      ->ignoreVCS(FALSE);
    $this->copyCodebase($finder->getIterator());

    $component_paths = [];
    // During the dataProvider phase, there is not a workspace directory yet.
    // So we will find relative paths and assemble them with the workspace
    // path later.
    $drupal_root = self::getDrupalRootStatic();
    $composer_json_finder = self::getComponentPathsFinder($drupal_root);

    /** @var \Symfony\Component\Finder\SplFileInfo $path */
    foreach ($composer_json_finder->getIterator() as $path) {
      $component_paths[$path->getRelativePath()] = '/' . $path->getRelativePath();
    }

    // Adding composer path repositories for every component to every component
    // is time consuming, so add a path repository for each component in
    // parallel.
    $repo_paths = $component_paths;
    foreach ($repo_paths as $repo_path) {
      $commands = [];
      foreach ($component_paths as $component_path) {
        $working_dir = $this->getWorkingPath() . static::$componentsPath . $component_path;
        $package_name = 'drupal/core' . strtolower(preg_replace('/[A-Z]/', '-$0', substr($repo_path, 1)));
        $path_repo = $this->getWorkingPath() . static::$componentsPath . $repo_path;
        $repo_name = strtolower($repo_path);
        // Add path repositories with the current version number to the current
        // package under test.
        $drupal_version = Composer::drupalVersionBranch();
        $command_line = "composer repository add $repo_name " .
          "'{\"type\": \"path\",\"url\": \"$path_repo\",\"options\": {\"versions\": {\"$package_name\": \"$drupal_version\"}}}' --working-dir=$working_dir";
        $commands[$repo_path][$component_path] = Process::fromShellCommandline($command_line);
        $commands[$repo_path][$component_path]->setWorkingDirectory($this->getWorkingPath($working_dir))
          ->setTimeout(360)
          ->setIdleTimeout(360);
        $commands[$repo_path][$component_path]->start();
      }
      foreach ($component_paths as $component_path) {
        if ($commands[$repo_path][$component_path]->isRunning()) {
          $commands[$repo_path][$component_path]->wait();
        }
      }
    }
    foreach ($component_paths as $component_path) {
      $working_dir = $this->getWorkingPath() . static::$componentsPath . $component_path;
      // Perform the installation.
      $this->executeCommand("COMPOSER_NO_SECURITY_BLOCKING=1 composer install --working-dir=$working_dir --no-interaction --no-progress");
      $this->assertCommandSuccessful();
    }
  }

}
