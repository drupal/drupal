<?php

declare(strict_types=1);

namespace Drupal\fixture_manipulator;

use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Utility\NestedArray;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Drupal\Component\Serialization\Yaml;

/**
 * Manipulates a test fixture using Composer commands.
 *
 * The composer.json file CANNOT be safely created or modified using the
 * json_encode() function, because Composer does not use a real JSON parser — it
 * updates composer.json using \Composer\Json\JsonManipulator, which is known to
 * choke in a number of scenarios.
 *
 * @see https://www.drupal.org/i/3346628
 */
class FixtureManipulator {

  protected const PATH_REPO_STATE_KEY = self::class . '-path-repo-base';

  /**
   * Whether changes are currently being committed.
   *
   * @var bool
   */
  private bool $committingChanges = FALSE;

  /**
   * Arguments to manipulator functions.
   *
   * @var array
   */
  private array $manipulatorArguments = [];

  /**
   * Whether changes have been committed.
   *
   * @var bool
   */
  protected bool $committed = FALSE;

  /**
   * The fixture directory.
   *
   * @var string
   */
  protected string $dir;

  /**
   * Validate the fixtures still passes `composer validate`.
   */
  private function validateComposer(): void {
    /** @var \PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface $runner */
    $runner = \Drupal::service(ComposerProcessRunnerInterface::class);
    $runner->run([
      'validate',
      '--check-lock',
      '--no-check-publish',
      '--with-dependencies',
      '--no-interaction',
      '--ansi',
      '--no-cache',
      "--working-dir={$this->dir}",
      // Unlike ComposerInspector::validate(), explicitly do NOT validate
      // plugins, to allow for testing edge cases.
      '--no-plugins',
      // Dummy packages are not meant for publishing, so do not validate that.
      '--no-check-publish',
      '--no-check-version',
    ]);
  }

  /**
   * Adds a package.
   *
   * @param array $package
   *   A Composer package definition. Must include the `name` and `type` keys.
   * @param bool $is_dev_requirement
   *   Whether the package is a development requirement.
   * @param bool $allow_plugins
   *   Whether to use the '--no-plugins' option.
   * @param array|null $extra_files
   *   An array extra files to create in the package. The keys are the file
   *   paths under package and values are the file contents.
   */
  public function addPackage(array $package, bool $is_dev_requirement = FALSE, bool $allow_plugins = FALSE, ?array $extra_files = NULL): self {
    if (!$this->committingChanges) {
      // To pass Composer validation all packages must have a version specified.
      if (!isset($package['version'])) {
        $package['version'] = '1.2.3';
      }
      $this->queueManipulation('addPackage', [$package, $is_dev_requirement, $allow_plugins, $extra_files]);
      return $this;
    }

    // Basic validation so we can defer the rest to `composer` commands.
    foreach (['name', 'type'] as $required_key) {
      if (!isset($package[$required_key])) {
        throw new \UnexpectedValueException("The '$required_key' is required when calling ::addPackage().");
      }
    }
    if (!preg_match('/\w+\/\w+/', $package['name'])) {
      throw new \UnexpectedValueException(sprintf("'%s' is not a valid package name.", $package['name']));
    }

    // `composer require` happily will re-require already required packages.
    // Prevent test authors from thinking this has any effect when it does not.
    $json = $this->runComposerCommand(['show', '--name-only', '--format=json'])->stdout;
    $installed_package_names = array_column(json_decode($json)?->installed ?? [], 'name');
    if (in_array($package['name'], $installed_package_names)) {
      throw new \LogicException(sprintf("Expected package '%s' to not be installed, but it was.", $package['name']));
    }

    $repo_path = $this->addRepository($package);
    if (is_null($extra_files) && isset($package['type']) && in_array($package['type'], ['drupal-module', 'drupal-theme', 'drupal-profile'], TRUE)) {
      // For Drupal projects if no files are provided create an info.yml file
      // that assumes the project and package names match.
      [, $package_name] = explode('/', $package['name']);
      $project_name = str_replace('-', '_', $package_name);
      $project_info_data = [
        'name' => $package['name'],
        'project' => $project_name,
      ];
      $extra_files["$project_name.info.yml"] = Yaml::encode($project_info_data);
    }
    if (!empty($extra_files)) {
      $fs = new SymfonyFileSystem();
      foreach ($extra_files as $file_name => $file_contents) {
        if (str_contains($file_name, DIRECTORY_SEPARATOR)) {
          $file_dir = dirname("$repo_path/$file_name");
          if (!is_dir($file_dir)) {
            $fs->mkdir($file_dir);
          }
        }
        assert(file_put_contents("$repo_path/$file_name", $file_contents) !== FALSE);
      }
    }
    return $this->requirePackage($package['name'], $package['version'], $is_dev_requirement, $allow_plugins);
  }

  /**
   * Requires a package.
   *
   * @param string $package
   *   A package name.
   * @param string $version
   *   A version constraint.
   * @param bool $is_dev_requirement
   *   Whether the package is a development requirement.
   * @param bool $allow_plugins
   *   Whether to use the '--no-plugins' option.
   */
  public function requirePackage(string $package, string $version, bool $is_dev_requirement = FALSE, bool $allow_plugins = FALSE): self {
    if (!$this->committingChanges) {
      $this->queueManipulation('requirePackage', func_get_args());
      return $this;
    }

    $command_options = ['require', "$package:$version"];
    if ($is_dev_requirement) {
      $command_options[] = '--dev';
    }
    // Unlike ComposerInspector::validate(), explicitly do NOT validate plugins.
    if (!$allow_plugins) {
      $command_options[] = '--no-plugins';
    }
    $this->runComposerCommand($command_options);
    return $this;
  }

  /**
   * Modifies a package's composer.json properties.
   *
   * @param string $package_name
   *   The name of the package to modify.
   * @param string $version
   *   The version to use for the modified package. Can be the same as the
   *   original version, or a different version.
   * @param array $config
   *   The config to be added to the package's composer.json.
   * @param bool $is_dev_requirement
   *   Whether the package is a development requirement.
   *
   * @see \Composer\Command\ConfigCommand
   */
  public function modifyPackageConfig(string $package_name, string $version, array $config, bool $is_dev_requirement = FALSE): self {
    if (!$this->committingChanges) {
      $this->queueManipulation('modifyPackageConfig', func_get_args());
      return $this;
    }
    $package = [
      'name' => $package_name,
      'version' => $version,
    ] + $config;
    $this->addRepository($package);
    $this->runComposerCommand(array_filter(['require', "$package_name:$version", $is_dev_requirement ? '--dev' : NULL]));
    return $this;
  }

  /**
   * Sets a package version.
   *
   * @param string $package_name
   *   The package name.
   * @param string $version
   *   The version.
   * @param bool $is_dev_requirement
   *   Whether the package is a development requirement.
   *
   * @return $this
   */
  public function setVersion(string $package_name, string $version, bool $is_dev_requirement = FALSE): self {
    if (!$this->committingChanges) {
      $this->queueManipulation('setVersion', func_get_args());
      return $this;
    }
    return $this->modifyPackageConfig($package_name, $version, [], $is_dev_requirement);
  }

  /**
   * Removes a package.
   *
   * @param string $name
   *   The name of the package to remove.
   * @param bool $is_dev_requirement
   *   Whether the package is a developer requirement.
   */
  public function removePackage(string $name, bool $is_dev_requirement = FALSE): self {
    if (!$this->committingChanges) {
      $this->queueManipulation('removePackage', func_get_args());
      return $this;
    }

    $output = $this->runComposerCommand(array_filter(['remove', $name, $is_dev_requirement ? '--dev' : NULL]));
    // `composer remove` will not set exit code 1 whenever a non-required
    // package is being removed.
    // @see \Composer\Command\RemoveCommand
    if (str_contains($output->stderr, 'not required in your composer.json and has not been removed')) {
      $output->stderr = str_replace("./composer.json has been updated\n", '', $output->stderr);
      throw new \LogicException($output->stderr);
    }
    return $this;
  }

  /**
   * Adds a project at a path.
   *
   * @param string $path
   *   The path.
   * @param string|null $project_name
   *   (optional) The project name. If none is specified the last part of the
   *   path will be used.
   * @param string|null $file_name
   *   (optional) The file name. If none is specified the project name will be
   *   used.
   */
  public function addProjectAtPath(string $path, ?string $project_name = NULL, ?string $file_name = NULL): self {
    if (!$this->committingChanges) {
      $this->queueManipulation('addProjectAtPath', func_get_args());
      return $this;
    }
    $path = $this->dir . "/$path";
    if (file_exists($path)) {
      throw new \LogicException("'$path' path already exists.");
    }
    $fs = new SymfonyFileSystem();
    $fs->mkdir($path);
    if ($project_name === NULL) {
      $project_name = basename($path);
    }
    if ($file_name === NULL) {
      $file_name = "$project_name.info.yml";
    }
    assert(file_put_contents("$path/$file_name", Yaml::encode(['project' => $project_name])) !== FALSE);
    return $this;
  }

  /**
   * Modifies core packages.
   *
   * @param string $version
   *   Target version.
   */
  public function setCorePackageVersion(string $version): self {
    $this->setVersion('drupal/core', $version);
    $this->setVersion('drupal/core-recommended', $version);
    $this->setVersion('drupal/core-dev', $version);
    return $this;
  }

  /**
   * Modifies the project root's composer.json properties.
   *
   * @see \Composer\Command\ConfigCommand
   *
   * @param array $additional_config
   *   The configuration to add.
   * @param bool $update_lock
   *   Whether to run composer update --lock. Defaults to FALSE.
   */
  public function addConfig(array $additional_config, bool $update_lock = FALSE): self {
    if (empty($additional_config)) {
      throw new \InvalidArgumentException('No config to add.');
    }

    if (!$this->committingChanges) {
      $this->queueManipulation('addConfig', func_get_args());
      return $this;
    }
    $clean_value = function ($value) {
      return $value === FALSE ? 'false' : $value;
    };

    foreach ($additional_config as $key => $value) {
      $command = ['config'];
      if (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_SLASHES);
        $command[] = '--json';
      }
      else {
        $value = $clean_value($value);
      }
      $command[] = $key;
      $command[] = $value;
      $this->runComposerCommand($command);
    }
    if ($update_lock) {
      $this->runComposerCommand(['update', '--lock']);
    }

    return $this;
  }

  /**
   * Commits the changes to the directory.
   *
   * @param string $dir
   *   The directory to commit the changes to.
   */
  public function commitChanges(string $dir): self {
    $this->doCommitChanges($dir);
    $this->committed = TRUE;
    return $this;
  }

  /**
   * Commits all the changes.
   *
   * @param string $dir
   *   The directory to commit the changes to.
   */
  final protected function doCommitChanges(string $dir): void {
    if ($this->committed) {
      throw new \BadMethodCallException('Already committed.');
    }
    $this->dir = $dir;
    $this->setUpRepos();
    $this->committingChanges = TRUE;
    $manipulator_arguments = $this->getQueuedManipulationItems();
    $this->clearQueuedManipulationItems();
    foreach ($manipulator_arguments as $method => $argument_sets) {
      // @todo Attempt to make fewer Composer calls in
      //   https://drupal.org/i/3345639.
      foreach ($argument_sets as $argument_set) {
        $this->{$method}(...$argument_set);
      }
    }
    $this->committed = TRUE;
    $this->committingChanges = FALSE;
  }

  public function updateLock(): self {
    $this->runComposerCommand(['update', '--lock']);
    return $this;
  }

  /**
   * Ensure that changes were committed before object is destroyed.
   */
  public function __destruct() {
    if (!$this->committed && !empty($this->manipulatorArguments)) {
      throw new \LogicException('commitChanges() must be called.');
    }
  }

  /**
   * Creates an empty .git folder after being provided a path.
   */
  public function addDotGitFolder(string $path): self {
    if (!$this->committingChanges) {
      $this->queueManipulation('addDotGitFolder', func_get_args());
      return $this;
    }
    if (!is_dir($path)) {
      throw new \LogicException("No directory exists at $path.");
    }
    $fs = new SymfonyFileSystem();
    $git_directory_path = $path . "/.git";
    if (is_dir($git_directory_path)) {
      throw new \LogicException("A .git directory already exists at $path.");
    }
    $fs->mkdir($git_directory_path);
    return $this;
  }

  /**
   * Queues manipulation arguments to be called in ::doCommitChanges().
   *
   * @param string $method
   *   The method name.
   * @param array $arguments
   *   The arguments.
   */
  protected function queueManipulation(string $method, array $arguments): void {
    $this->manipulatorArguments[$method][] = $arguments;
  }

  /**
   * Clears all queued manipulation items.
   */
  protected function clearQueuedManipulationItems(): void {
    $this->manipulatorArguments = [];
  }

  /**
   * Gets all queued manipulation items.
   *
   * @return array
   *   The queued manipulation items as set by calls to ::queueManipulation().
   */
  protected function getQueuedManipulationItems(): array {
    return $this->manipulatorArguments;
  }

  protected function runComposerCommand(array $command_options): OutputCallbackInterface {
    $plain_output = new class() implements OutputCallbackInterface {
      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
      public string $stdout = '';
      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
      public string $stderr = '';

      /**
       * {@inheritdoc}
       */
      public function __invoke(OutputTypeEnum $type, string $buffer): void {
        if ($type === OutputTypeEnum::OUT) {
          $this->stdout .= $buffer;
        }
        elseif ($type === OutputTypeEnum::ERR) {
          $this->stderr .= $buffer;
        }
      }

      /**
       * {@inheritdoc}
       */
      public function clearErrorOutput(): void {
        throw new \LogicException("Unexpected call to clearErrorOutput().");
      }

      /**
       * {@inheritdoc}
       */
      public function clearOutput(): void {
        throw new \LogicException("Unexpected call to clearOutput().");
      }

      /**
       * {@inheritdoc}
       */
      public function getErrorOutput(): array {
        throw new \LogicException("Unexpected call to getErrorOutput().");
      }

      /**
       * {@inheritdoc}
       */
      public function getOutput(): array {
        throw new \LogicException("Unexpected call to getOutput().");
      }

    };
    /** @var \PhpTuf\ComposerStager\API\Process\Service\ComposerProcessRunnerInterface $runner */
    $runner = \Drupal::service(ComposerProcessRunnerInterface::class);
    $command_options[] = "--working-dir={$this->dir}";
    $runner->run($command_options, callback: $plain_output);
    return $plain_output;
  }

  /**
   * Transform the received $package into options for `composer init`.
   *
   * @param array $package
   *   A Composer package definition. Must include the `name` and `type` keys.
   *
   * @return array
   *   The corresponding `composer init` options.
   */
  private static function getComposerInitOptionsForPackage(array $package): array {
    return array_filter(array_map(function ($k, $v) {
      switch ($k) {
        case 'name':
        case 'description':
        case 'type':
          return "--$k=$v";

        case 'require':
        case 'require-dev':
          if (empty($v)) {
            return NULL;
          }
          $requirements = array_map(
            fn(string $req_package, string $req_version): string => "$req_package:$req_version",
            array_keys($v),
            array_values($v)
          );
          return "--$k=" . implode(',', $requirements);

        case 'version':
          // This gets set in the repository metadata itself.
          return NULL;

        case 'extra':
          // Cannot be set using `composer init`, only `composer config` can.
          return NULL;

        default:
          throw new \InvalidArgumentException($k);
      }
    }, array_keys($package), array_values($package)));
  }

  /**
   * Creates a path repo.
   *
   * @param array $package
   *   A Composer package definition. Must include the `name` and `type` keys.
   * @param string $repo_path
   *   The path at which to create a path repo for this package.
   * @param string|null $original_repo_path
   *   If NULL: this is the first version of this package. Otherwise: a string
   *   containing the path repo to the first version of this package. This will
   *   be used to automatically inherit the same files (typically *.info.yml).
   */
  private function createPathRepo(array $package, string $repo_path, ?string $original_repo_path): void {
    $fs = new SymfonyFileSystem();
    if (is_dir($repo_path)) {
      throw new \LogicException("A path repo already exists at $repo_path.");
    }
    // Create the repo if it does not exist.
    $fs->mkdir($repo_path);
    // Forks also get the original's additional files (e.g. *.info.yml files).
    if ($original_repo_path) {
      $fs->mirror($original_repo_path, $repo_path);
      // composer.json will be freshly generated by `composer init` below.
      $fs->remove($repo_path . '/composer.json');
    }
    // Switch the working directory from project root to repo path.
    $project_root_dir = $this->dir;
    $this->dir = $repo_path;
    // Create a composer.json file using `composer init`.
    $this->runComposerCommand(['init', ...static::getComposerInitOptionsForPackage($package)]);
    // Set the `extra` property in the generated composer.json file using
    // `composer config`, because `composer init` does not support it.
    foreach ($package['extra'] ?? [] as $extra_property => $extra_value) {
      $this->runComposerCommand(['config', "extra.$extra_property", '--json', json_encode($extra_value, JSON_UNESCAPED_SLASHES)]);
    }
    // Restore the project root as the working directory.
    $this->dir = $project_root_dir;
  }

  /**
   * Adds a path repository.
   *
   * @param array $package
   *   A Composer package definition. Must include the `name` and `type` keys.
   *
   * @return string
   *   The repository path.
   */
  private function addRepository(array $package): string {
    $name = $package['name'];
    $path_repo_base = \Drupal::state()->get(self::PATH_REPO_STATE_KEY);
    $repo_path = "$path_repo_base/" . str_replace('/', '--', $name);

    // Determine if the given $package is a new package or a fork of an existing
    // one (that means it's either the same version but with other metadata, or
    // a new version with other metadata). Existing path repos are never
    // modified, not even if the same version of a package is assigned other
    // metadata. This allows always comparing with the original metadata.
    $is_new_or_fork = !is_dir($repo_path) ? 'new' : 'fork';
    if ($is_new_or_fork === 'fork') {
      $original_composer_json_path = $repo_path . DIRECTORY_SEPARATOR . 'composer.json';
      $original_repo_path = $repo_path;
      $original_composer_json_data = json_decode(file_get_contents($original_composer_json_path), TRUE, flags: JSON_THROW_ON_ERROR);
      $forked_composer_json_data = NestedArray::mergeDeep($original_composer_json_data, $package);
      if ($original_composer_json_data === $forked_composer_json_data) {
        throw new \LogicException(sprintf('Nothing is actually different in this fork of the package %s.', $package['name']));
      }
      $package = $forked_composer_json_data;
      $repo_path .= "--{$package['version']}";
      // Cannot create multiple forks with the same version. This is likely
      // due to a test simulating a failed Stage::apply().
      if (!is_dir($repo_path)) {
        $this->createPathRepo($package, $repo_path, $original_repo_path);
      }
    }
    else {
      $this->createPathRepo($package, $repo_path, NULL);
    }

    // Add the package to the Composer repository defined for the site.
    $packages_json = $this->dir . '/packages.json';
    $packages_data = file_get_contents($packages_json);
    $packages_data = json_decode($packages_data, TRUE, flags: JSON_THROW_ON_ERROR);

    $version = $package['version'];
    $package['dist'] = [
      'type' => 'path',
      'url' => $repo_path,
    ];
    $packages_data['packages'][$name][$version] = $package;
    assert(file_put_contents($packages_json, json_encode($packages_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== FALSE);

    return $repo_path;
  }

  /**
   * Sets up the path repos at absolute paths.
   *
   * @param bool $composer_refresh
   *   Whether to run composer update --lock && composer install. Defaults to
   *   FALSE.
   */
  public function setUpRepos($composer_refresh = FALSE): void {
    $fs = new SymfonyFileSystem();
    $path_repo_base = \Drupal::state()->get(self::PATH_REPO_STATE_KEY);
    if (empty($path_repo_base)) {
      $path_repo_base = FileSystem::getOsTemporaryDirectory() . '/base-repo-' . microtime(TRUE) . rand(0, 10000);
      \Drupal::state()->set(self::PATH_REPO_STATE_KEY, $path_repo_base);
      // Copy the existing repos that were used to make the fixtures into the
      // new folder.
      $fs->mirror(__DIR__ . '/../../../fixtures/path_repos', $path_repo_base);
    }
    // Update all the repos in the composer.json file to point to the new
    // repos at the absolute path.
    $composer_json = file_get_contents($this->dir . '/packages.json');
    assert(file_put_contents($this->dir . '/packages.json', str_replace('../path_repos/', "$path_repo_base/", $composer_json)) !== FALSE);
    if ($composer_refresh) {
      $this->runComposerCommand(['update', '--lock']);
      $this->runComposerCommand(['install']);
    }
  }

}
