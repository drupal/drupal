<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Composer\Component;

use Drupal\BuildTests\Composer\ComposerBuildTestBase;
use Drupal\Composer\Composer;

/**
 * Demonstrate that the Component generator responds to release tagging.
 *
 * @group #slow
 * @group Composer
 * @group Component
 *
 * @coversNothing
 */
class ComponentsTaggedReleaseTest extends ComposerBuildTestBase {

  /**
   * Highly arbitrary version and constraint expectations.
   *
   * @return array
   *   - First element is the tag that should be applied to \Drupal::version.
   *   - Second element is the resulting constraint which should be present in
   *     the component core dependencies.
   */
  public function providerVersionConstraint(): array {
    return [
      // [Tag, constraint]
      '1.0.x-dev' => ['1.0.x-dev', '1.0.x-dev'],
      '1.0.0-beta1' => ['1.0.0-beta1', '1.0.0-beta1'],
      '1.0.0-rc1' => ['1.0.0-rc1', '1.0.0-rc1'],
      '1.0.0' => ['1.0.0', '^1.0'],
    ];
  }

  /**
   * Validate release tagging and regeneration of dependencies.
   *
   * @dataProvider providerVersionConstraint
   */
  public function testReleaseTagging(string $tag, string $constraint): void {
    $this->copyCodebase();
    $drupal_root = $this->getWorkspaceDirectory();

    // Set the core version.
    Composer::setDrupalVersion($drupal_root, $tag);
    $this->assertDrupalVersion($tag, $drupal_root);

    // Emulate the release script.
    // @see https://github.com/xjm/drupal_core_release/blob/main/tag.sh
    $this->executeCommand("COMPOSER_ROOT_VERSION=\"$tag\" composer update drupal/core*");
    $this->assertCommandSuccessful();
    $this->assertErrorOutputContains('generateComponentPackages');

    // Find all the components.
    $component_finder = $this->getComponentPathsFinder($drupal_root);

    // Loop through all the component packages.
    /** @var \Symfony\Component\Finder\SplFileInfo $composer_json */
    foreach ($component_finder->getIterator() as $composer_json) {
      $composer_json_data = json_decode(file_get_contents($composer_json->getPathname()), TRUE);
      $requires = array_merge(
        $composer_json_data['require'] ?? [],
        $composer_json_data['require-dev'] ?? []
      );
      // Required packages from drupal/core-* should have our constraint.
      foreach ($requires as $package => $req_constraint) {
        if (str_contains($package, 'drupal/core-')) {
          $this->assertEquals($constraint, $req_constraint);
        }
      }
    }
  }

}
