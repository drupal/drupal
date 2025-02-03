<?php

namespace Drupal\Composer\Generator;

use Drupal\Composer\Generator\Builder\DrupalCoreRecommendedBuilder;
use Drupal\Composer\Generator\Builder\DrupalDevDependenciesBuilder;
use Drupal\Composer\Generator\Builder\DrupalPinnedDevDependenciesBuilder;
use Drupal\Composer\Generator\Util\DrupalCoreComposer;
use Composer\Util\Filesystem;
use Composer\IO\IOInterface;

/**
 * Generates metapackages.
 */
class PackageGenerator {

  /**
   * Base directory where generated projects are written.
   *
   * @var string
   */
  protected $generatedProjectBaseDir;

  /**
   * PackageGenerator constructor.
   */
  public function __construct() {
    $this->generatedProjectBaseDir = dirname(__DIR__) . '/Metapackage';
  }

  /**
   * Generate Drupal's metapackages whenever composer.lock is updated.
   *
   * @param \Composer\IO\IOInterface $io
   *   Composer IO object for interacting with the user.
   * @param string $base_dir
   *   Directory where drupal/drupal repository is located.
   */
  public function generate(IOInterface $io, $base_dir) {
    // General information from drupal/drupal and drupal/core composer.json
    // and composer.lock files.
    $drupalCoreInfo = DrupalCoreComposer::createFromPath($base_dir);

    // Exit early if there is no composer.lock file.
    if (empty($drupalCoreInfo->composerLock())) {
      return;
    }

    // Run all of our available builders.
    $builders = $this->builders();
    $changed = FALSE;
    foreach ($builders as $builder_class) {
      $builder = new $builder_class($drupalCoreInfo);
      $changed |= $this->generateMetapackage($io, $builder);
    }

    // Remind the user not to miss files in a patch.
    if ($changed) {
      $io->write("If you make a patch, ensure that the files above are included.");
    }
  }

  /**
   * Returns a list of metapackage builders.
   *
   * @return BuilderInterface[]
   *   An array of BuilderInterface objects.
   */
  protected function builders() {
    return [
      DrupalCoreRecommendedBuilder::class,
      DrupalDevDependenciesBuilder::class,
      DrupalPinnedDevDependenciesBuilder::class,
    ];
  }

  /**
   * Generate one metapackage.
   *
   * @param \Composer\IO\IOInterface $io
   *   Composer IO object for interacting with the user.
   * @param BuilderInterface $builder
   *   An object that can build a metapackage.
   *
   * @return bool
   *   TRUE if the generated metapackage is different than what is on disk.
   */
  protected function generateMetapackage(IOInterface $io, BuilderInterface $builder) {

    // Load the existing composer.json file for drupal/core-recommended
    $relative_path = $builder->getPath() . '/composer.json';
    $composer_json_path = $this->generatedProjectBaseDir . '/' . $relative_path;
    $original_composer_json = file_exists($composer_json_path) ? file_get_contents($composer_json_path) : '';

    // Get the composer.json file from the builder.
    $composer_json_data = $builder->getPackage();
    $updated_composer_json = static::encode($composer_json_data);

    // Exit early if nothing changed.
    if (trim($original_composer_json, " \t\r\0\x0B") == trim($updated_composer_json, " \t\r\0\x0B")) {
      return FALSE;
    }

    // Warn the user that a metapackage file has been updated..
    $io->write("Updated metapackage file <info>composer/Metapackage/$relative_path</info>.");

    // Write the composer.json file back to disk
    $fs = new Filesystem();
    $fs->ensureDirectoryExists(dirname($composer_json_path));
    file_put_contents($composer_json_path, $updated_composer_json);

    return TRUE;
  }

  /**
   * Utility function to encode metapackage json in a consistent way.
   *
   * @param array $composer_json_data
   *   Data to encode into a json string.
   *
   * @return string
   *   Encoded version of provided json data.
   */
  public static function encode($composer_json_data) {
    return json_encode($composer_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
  }

}
