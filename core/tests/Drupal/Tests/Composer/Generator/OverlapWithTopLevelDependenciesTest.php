<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Generator;

use PHPUnit\Framework\TestCase;

/**
 * Tests DrupalCoreRecommendedBuilder.
 *
 * @group Metapackage
 */
class OverlapWithTopLevelDependenciesTest extends TestCase {

  /**
   * Provides data for testOverlapWithTemplateProject().
   */
  public function templateProjectPathProvider() {
    return [
      [
        'composer/Template/RecommendedProject',
      ],
      [
        'composer/Template/LegacyProject',
      ],
    ];
  }

  /**
   * Tests top level and core-recommended dependencies do not overlap.
   *
   * @dataProvider templateProjectPathProvider
   *
   * @param string $template_project_path
   *   The path of the project template to test.
   */
  public function testOverlapWithTemplateProject($template_project_path) {
    $root = dirname(__DIR__, 6);
    // Read template project composer.json.
    $top_level_composer_json = json_decode(file_get_contents("$root/$template_project_path/composer.json"), TRUE);

    // Read drupal/core-recommended composer.json.
    $core_recommended_composer_json = json_decode(file_get_contents("$root/composer/Metapackage/CoreRecommended/composer.json"), TRUE);

    // Fail if any required project in the require section of the template
    // project also exists in core/recommended.
    foreach ($top_level_composer_json['require'] as $project => $version_constraint) {
      $this->assertArrayNotHasKey($project, $core_recommended_composer_json['require'], "Pinned project $project is also a top-level dependency of $template_project_path. This can expose a Composer bug. See https://www.drupal.org/project/drupal/issues/3134648 and https://github.com/composer/composer/issues/8882");
    }
  }

}
