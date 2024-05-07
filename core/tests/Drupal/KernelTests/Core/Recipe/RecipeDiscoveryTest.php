<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Recipe\RecipeDiscovery;
use Drupal\Core\Recipe\UnknownRecipeException;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Recipe\RecipeDiscovery
 * @group Recipe
 */
class RecipeDiscoveryTest extends KernelTestBase {

  /**
   * Tests that recipe discovery can find recipes.
   *
   * @testWith ["install_two_modules", "Install two modules"]
   *           ["recipe_include", "Recipe include"]
   */
  public function testRecipeDiscovery(string $recipe, string $name): void {
    $discovery = new RecipeDiscovery('core/tests/fixtures/recipes');
    $recipe = $discovery->getRecipe($recipe);
    $this->assertSame($name, $recipe->name);
  }

  /**
   * Tests the exception thrown when recipe discovery cannot find a recipe.
   *
   * @testWith ["no_recipe"]
   *           ["does_not_exist"]
   */
  public function testRecipeDiscoveryException(string $recipe): void {
    $discovery = new RecipeDiscovery('core/tests/fixtures/recipes');
    try {
      $discovery->getRecipe($recipe);
      $this->fail('Expected exception not thrown');
    }
    catch (UnknownRecipeException $e) {
      $this->assertSame($recipe, $e->recipe);
      $this->assertSame('core/tests/fixtures/recipes', $e->searchPath);
      $this->assertSame('Can not find the ' . $recipe . ' recipe, search path: ' . $e->searchPath, $e->getMessage());
    }
  }

}
