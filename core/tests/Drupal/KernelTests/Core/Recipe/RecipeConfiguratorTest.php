<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeConfigurator;
use Drupal\Core\Recipe\RecipeDiscovery;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\Core\Recipe\RecipeConfigurator
 * @group Recipe
 */
class RecipeConfiguratorTest extends KernelTestBase {

  public function testRecipeConfigurator(): void {
    $discovery = new RecipeDiscovery('core/tests/fixtures/recipes');
    $recipe_configurator = new RecipeConfigurator(
      ['install_two_modules', 'install_node_with_config', 'recipe_include'],
      $discovery
    );
    // Private method "listAllRecipes".
    $reflection = new \ReflectionMethod('\Drupal\Core\Recipe\RecipeConfigurator', 'listAllRecipes');

    // Test methods.
    /** @var \Drupal\Core\Recipe\Recipe[] $recipes */
    $recipes = (array) $reflection->invoke($recipe_configurator);
    $recipes_names = array_map(fn(Recipe $recipe) => $recipe->name, $recipes);
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

}
