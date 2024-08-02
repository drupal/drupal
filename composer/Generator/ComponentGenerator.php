<?php

namespace Drupal\Composer\Generator;

use Composer\IO\IOInterface;
use Composer\Semver\VersionParser;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Drupal\Composer\Composer;
use Drupal\Composer\Generator\Util\DrupalCoreComposer;
use Drupal\Composer\Util\SemanticVersion;
use Symfony\Component\Finder\Finder;

/**
 * Reconciles Drupal component dependencies with core.
 */
class ComponentGenerator {

  /**
   * Relative path from Drupal root to the component directory.
   *
   * @var string
   */
  protected static $relativeComponentPath = 'core/lib/Drupal/Component';

  /**
   * Full path to the component directory.
   *
   * @var string
   */
  protected $componentBaseDir;

  /**
   * Data from drupal/drupal's composer.json file.
   *
   * @var \Drupal\Composer\Generator\Util\DrupalCoreComposer
   */
  protected $drupalProjectInfo;

  /**
   * Data from drupal/core's composer.json file.
   *
   * @var \Drupal\Composer\Generator\Util\DrupalCoreComposer
   */
  protected $drupalCoreInfo;

  /**
   * ComponentGenerator constructor.
   */
  public function __construct() {
    $this->componentBaseDir = dirname(__DIR__, 2) . '/' . static::$relativeComponentPath;
  }

  /**
   * Find all the composer.json files for components.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder object with all the composer.json files for components.
   */
  public function getComponentPathsFinder(): Finder {
    $composer_json_finder = new Finder();
    $composer_json_finder->name('composer.json')
      ->in($this->componentBaseDir)
      ->ignoreUnreadableDirs()
      ->depth(1);
    return $composer_json_finder;
  }

  /**
   * Reconcile Drupal's components whenever composer.lock is updated.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   * @param string $base_dir
   *   Directory where drupal/drupal repository is located.
   */
  public function generate(Event $event, string $base_dir): void {
    $io = $event->getIO();
    // General information from drupal/drupal and drupal/core composer.json
    // and composer.lock files.
    $this->drupalProjectInfo = DrupalCoreComposer::createFromPath($base_dir);
    $this->drupalCoreInfo = DrupalCoreComposer::createFromPath($base_dir . '/core');

    $changed = FALSE;
    /** @var \Symfony\Component\Finder\SplFileInfo $component_composer_json */
    foreach ($this->getComponentPathsFinder()->getIterator() as $component_composer_json) {
      $changed |= $this->generateComponentPackage($event, $component_composer_json->getRelativePathname());
    }

    // Remind the user not to miss files in a patch.
    if ($changed) {
      $io->write("If you make a patch, ensure that the files above are included.");
    }
  }

  /**
   * Generate the component JSON files.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   * @param string $component_pathname
   *   Relative path to the composer.json file for a component.
   *
   * @return bool
   *   TRUE if the generated component package is different from what is on
   *   disk.
   */
  protected function generateComponentPackage(Event $event, string $component_pathname): bool {
    $io = $event->getIO();
    $composer_json_path = $this->componentBaseDir . '/' . $component_pathname;
    $original_composer_json = file_exists($composer_json_path) ? file_get_contents($composer_json_path) : '';

    // Modify the original data.
    $composer_json_data = $this->getPackage($io, $original_composer_json);
    $updated_composer_json = static::encode($composer_json_data);

    // Exit early if nothing changed.
    if (trim($original_composer_json, " \t\r\0\x0B") === trim($updated_composer_json, " \t\r\0\x0B")) {
      return FALSE;
    }

    // Warn the user that a component file has been updated.
    $display_path = static::$relativeComponentPath . '/' . $component_pathname;
    $io->write("Updated component file <info>$display_path</info>.");

    // Write the composer.json file back to disk.
    $fs = new Filesystem();
    $fs->ensureDirectoryExists(dirname($composer_json_path));
    file_put_contents($composer_json_path, $updated_composer_json);

    return TRUE;
  }

  /**
   * Reconcile component dependencies with core.
   *
   * @param \Composer\IO\IOInterface $io
   *   IO object for messages to the user.
   * @param string $original_json
   *   Contents of the component's composer.json file.
   *
   * @return array
   *   Structured data to be turned back into JSON.
   */
  protected function getPackage(IOInterface $io, string $original_json): array {
    $original_data = json_decode($original_json, TRUE);
    $package_data = array_merge($original_data, $this->initialPackageMetadata());

    $core_info = $this->drupalCoreInfo->rootComposerJson();

    $stability = VersionParser::parseStability(\Drupal::VERSION);

    // List of packages which we didn't find in either core requirement.
    $not_in_core = [];

    // Traverse required packages.
    foreach (array_keys($original_data['require'] ?? []) as $package_name) {
      // Reconcile locked constraints from drupal/drupal. We might have a locked
      // version of a dependency that's not present in drupal/core.
      if ($info = $this->drupalProjectInfo->packageLockInfo($package_name)) {
        $package_data['require'][$package_name] = $info['version'];
      }
      // The package wasn't in the lock file, which means we need to tell the
      // user. But there are some packages we want to exclude from this list.
      elseif ($package_name !== 'php' && !str_contains($package_name, 'drupal/core-')) {
        $not_in_core[$package_name] = $package_name;
      }

      // Reconcile looser constraints from drupal/core, and we're totally OK
      // with over-writing the locked ones from above.
      if ($constraint = $core_info['require'][$package_name] ?? FALSE) {
        $package_data['require'][$package_name] = $constraint;
      }

      // Reconcile dependencies on other Drupal components, so we can set the
      // constraint to our current version.
      if (str_contains($package_name, 'drupal/core-')) {
        if ($stability === 'stable') {
          // Set the constraint to ^maj.min.
          $package_data['require'][$package_name] = SemanticVersion::majorMinorConstraint(\Drupal::VERSION);
        }
        else {
          // For non-stable releases, set the constraint to the branch version.
          $package_data['require'][$package_name] = Composer::drupalVersionBranch();
          // Also for non-stable releases which depend on another component,
          // set the minimum stability. We do this so we can test build the
          // components. Minimum-stability is otherwise ignored for packages
          // which aren't the root package, so for any other purpose, this is
          // unneeded.
          $package_data['minimum-stability'] = $stability;
        }
      }
    }
    if ($not_in_core) {
      $io->error($package_data['name'] . ' requires packages not present in drupal/drupal: ' . implode(', ', $not_in_core));
    }

    return $package_data;
  }

  /**
   * Utility function to encode package json in a consistent way.
   *
   * @param array $composer_json_data
   *   Data to encode into a json string.
   *
   * @return string
   *   Encoded version of provided json data.
   */
  public static function encode(array $composer_json_data): string {
    return json_encode($composer_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
  }

  /**
   * Common default metadata for all components.
   *
   * @return array
   *   An array containing the common default metadata for all components.
   */
  protected function initialPackageMetadata(): array {
    return [
      'extra' => [
        '_readme' => [
          'This file was partially generated automatically. See: https://www.drupal.org/node/3293830',
        ],
      ],
      // Always reconcile PHP version.
      'require' => [
        'php' => '>=' . \Drupal::MINIMUM_PHP,
      ],
    ];
  }

}
