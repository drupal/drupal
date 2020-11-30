<?php

namespace Drupal\Tests\Composer\Generator;

use Drupal\Composer\Generator\Builder\DrupalCoreRecommendedBuilder;
use Drupal\Composer\Generator\Builder\DrupalDevDependenciesBuilder;
use Drupal\Composer\Generator\Builder\DrupalPinnedDevDependenciesBuilder;
use PHPUnit\Framework\TestCase;
use Drupal\Composer\Composer;

/**
 * Test DrupalCoreRecommendedBuilder.
 *
 * @group Metapackage
 */
class BuilderTest extends TestCase {

  /**
   * Test data for testBuilder.
   */
  public function builderTestData() {
    return [
      [
        DrupalCoreRecommendedBuilder::class,
        [
          'name' => 'drupal/core-recommended',
          'type' => 'metapackage',
          'description' => 'Locked core dependencies; require this project INSTEAD OF drupal/core.',
          'license' => 'GPL-2.0-or-later',
          'require' =>
          [
            'drupal/core' => Composer::drupalVersionBranch(),
            'symfony/polyfill-ctype' => 'v1.12.0',
            'symfony/yaml' => 'v3.4.32',
          ],
          'conflict' =>
          [
            'webflo/drupal-core-strict' => '*',
          ],
        ],
      ],

      [
        DrupalDevDependenciesBuilder::class,
        [
          'name' => 'drupal/core-dev',
          'type' => 'metapackage',
          'description' => 'require-dev dependencies from drupal/drupal; use in addition to drupal/core-recommended to run tests from drupal/core.',
          'license' => 'GPL-2.0-or-later',
          'require' =>
          [
            'behat/mink' => '^1.8',
          ],
          'conflict' =>
          [
            'webflo/drupal-core-require-dev' => '*',
          ],
        ],
      ],

      [
        DrupalPinnedDevDependenciesBuilder::class,
        [
          'name' => 'drupal/core-dev-pinned',
          'type' => 'metapackage',
          'description' => 'Pinned require-dev dependencies from drupal/drupal; use in addition to drupal/core-recommended to run tests from drupal/core.',
          'license' => 'GPL-2.0-or-later',
          'require' =>
          [
            'drupal/core' => Composer::drupalVersionBranch(),
            'behat/mink' => 'v1.8.0',
            'symfony/css-selector' => 'v4.3.5',
          ],
          'conflict' =>
          [
            'webflo/drupal-core-require-dev' => '*',
          ],
        ],
      ],

    ];
  }

  /**
   * Test all of the various kinds of builders.
   *
   * @dataProvider builderTestData
   */
  public function testBuilder($builderClass, $expected) {
    $fixtures = new Fixtures();
    $drupalCoreInfo = $fixtures->drupalCoreComposerFixture();

    $builder = new $builderClass($drupalCoreInfo);
    $generatedJson = $builder->getPackage();

    $this->assertEquals($expected, $generatedJson);
  }

}
