<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeConfigurator;
use Drupal\Core\Recipe\UnknownRecipeException;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Drupal\Core\Recipe\RecipeConfigurator.
 */
#[CoversClass(RecipeConfigurator::class)]
#[Group('Recipe')]
#[RunTestsInSeparateProcesses]
class RecipeConfiguratorTest extends KernelTestBase {

  /**
   * Tests the listing all the extensions installed by a recipe.
   *
   * @legacy-covers ::listAllExtensions
   */
  public function testRecipeConfigurator(): void {
    $recipe_configurator = new RecipeConfigurator(
      ['install_two_modules', 'install_node_with_config', 'recipe_include'],
      'core/tests/fixtures/recipes'
    );
    // Private method "listAllRecipes".
    $reflection = new \ReflectionMethod('\Drupal\Core\Recipe\RecipeConfigurator', 'listAllRecipes');

    // Test methods.
    /** @var \Drupal\Core\Recipe\Recipe[] $recipes */
    $recipes = (array) $reflection->invoke($recipe_configurator);
    $recipes_names = array_map(fn(Recipe $recipe): string => $recipe->name, $recipes);
    $recipe_extensions = $recipe_configurator->listAllExtensions();
    $expected_recipes_names = [
      'Install two modules',
      'Install node with config',
      'Recipe include',
    ];
    $expected_recipe_extensions = [
      'system',
      'user',
      'filter',
      'field',
      'text',
      'node',
      'dblog',
    ];

    $this->assertEquals($expected_recipes_names, $recipes_names);
    $this->assertEquals($expected_recipe_extensions, $recipe_extensions);
    $this->assertEquals(1, array_count_values($recipes_names)['Install node with config']);
    $this->assertEquals(1, array_count_values($recipe_extensions)['field']);
  }

  /**
   * Tests that RecipeConfigurator can load recipes.
   *
   * @legacy-covers ::getIncludedRecipe
   */
  #[TestWith(["install_two_modules", "Install two modules"])]
  #[TestWith(["recipe_include", "Recipe include"])]
  public function testIncludedRecipeLoader(string $recipe, string $name): void {
    $recipe = RecipeConfigurator::getIncludedRecipe('core/tests/fixtures/recipes', $recipe);
    $this->assertSame($name, $recipe->name);
  }

  /**
   * Tests exception thrown when RecipeConfigurator cannot find a recipe.
   *
   * @legacy-covers ::getIncludedRecipe
   */
  #[TestWith(["no_recipe"])]
  #[TestWith(["does_not_exist"])]
  public function testIncludedRecipeLoaderException(string $recipe): void {
    try {
      RecipeConfigurator::getIncludedRecipe('core/tests/fixtures/recipes', $recipe);
      $this->fail('Expected exception not thrown');
    }
    catch (UnknownRecipeException $e) {
      $this->assertSame($recipe, $e->recipe);
      $this->assertSame('core/tests/fixtures/recipes', $e->searchPath);
      $this->assertSame('Can not find the ' . $recipe . ' recipe, search path: ' . $e->searchPath, $e->getMessage());
    }
  }

}
