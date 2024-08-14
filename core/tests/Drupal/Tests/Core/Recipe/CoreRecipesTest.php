<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Recipe;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Finder\Finder;

/**
 * Tests that all core recipes have a generic test.
 *
 * @group Recipe
 */
class CoreRecipesTest extends UnitTestCase {

  /**
   * Data provider for ::testRecipeHasGenericTest().
   *
   * @return iterable<array<string>>
   *   An iterable containing paths to recipe files.
   */
  public static function providerRecipeHasGenericTest(): iterable {
    $finder = Finder::create()
      ->in([
        dirname(__DIR__, 5) . '/recipes',
      ])
      ->directories()
      // Recipes can't contain other recipes, so we don't need to search in
      // subdirectories.
      ->depth(0)
      // The Example recipe is for documentation only, and cannot be applied.
      ->notName(['example']);
    static::assertGreaterThan(0, count($finder), 'No core recipes were found.');

    $scenarios = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $recipe */
    foreach ($finder as $recipe) {
      $name = $recipe->getBasename();
      $scenarios[$name] = [
        $recipe->getPathname(),
      ];
    }
    return $scenarios;
  }

  /**
   * Test that a recipe has a generic test.
   *
   * @param string $path
   *   The path to the recipe file.
   *
   * @dataProvider providerRecipeHasGenericTest
   */
  public function testRecipeHasGenericTest(string $path): void {
    $this->assertFileExists($path . '/tests/src/Functional/GenericTest.php');
  }

}
