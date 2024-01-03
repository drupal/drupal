<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Generator;

use Drupal\Composer\Generator\Builder\DrupalCoreRecommendedBuilder;
use Drupal\Composer\Generator\Builder\DrupalDevDependenciesBuilder;
use Drupal\Composer\Generator\Builder\DrupalPinnedDevDependenciesBuilder;
use Drupal\Composer\Generator\PackageGenerator;
use Drupal\Composer\Generator\Util\DrupalCoreComposer;

use PHPUnit\Framework\TestCase;

/**
 * Test to see if the metapackages are up-to-date with the root composer.lock.
 *
 * @group Metapackage
 */
class MetapackageUpdateTest extends TestCase {

  /**
   * Provides test data for testUpdated.
   */
  public function updatedTestData() {
    return [
      [
        DrupalCoreRecommendedBuilder::class,
        'composer/Metapackage/CoreRecommended',
      ],
      [
        DrupalDevDependenciesBuilder::class,
        'composer/Metapackage/DevDependencies',
      ],
      [
        DrupalPinnedDevDependenciesBuilder::class,
        'composer/Metapackage/PinnedDevDependencies',
      ],
    ];
  }

  /**
   * Tests to see if the generated metapackages are in sync with composer.lock.
   *
   * Note that this is not a test of code correctness, but rather it merely
   * confirms if the package builder was used on the most recent set of
   * metapackages.
   *
   * See BuilderTest.php for a test that checks for code correctness.
   *
   * @param string $builderClass
   *   The metapackage builder to test.
   * @param string $path
   *   The relative path to the metapackage
   *
   *  @dataProvider updatedTestData
   */
  public function testUpdated($builderClass, $path) {
    // Create a DrupalCoreComposer for the System Under Test (current repo)
    $repositoryRoot = dirname(__DIR__, 6);
    $drupalCoreInfo = DrupalCoreComposer::createFromPath($repositoryRoot);

    // Rebuild the metapackage for the composer.json / composer.lock of
    // the current repo.
    $builder = new $builderClass($drupalCoreInfo);
    $generatedJson = $builder->getPackage();
    $generatedJson = PackageGenerator::encode($generatedJson);

    // Also load the most-recently-generated version of the metapackage.
    $loadedJson = file_get_contents("$repositoryRoot/$path/composer.json");

    // The generated json is the "expected", what we think the loaded
    // json would contain, if the current patch is generated correctly
    // (metapackages updated when composer.lock is updated).
    $version = str_replace('.0-dev', '.x-dev', \Drupal::VERSION);
    $message = <<< __EOT__
The rebuilt version of $path does not match what is in the source tree.

To fix, run:

    COMPOSER_ROOT_VERSION=$version composer update --lock

__EOT__;
    $this->assertEquals($generatedJson, $loadedJson, $message);
  }

}
