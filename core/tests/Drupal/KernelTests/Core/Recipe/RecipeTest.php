<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeFileException;
use Drupal\Core\Recipe\RecipePreExistingConfigException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Recipe\Recipe
 * @group Recipe
 */
class RecipeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field'];

  /**
   * @testWith ["no_extensions", "No extensions" , "Testing", [], "A recipe description"]
   *           ["install_two_modules", "Install two modules" , "Content type", ["filter", "text", "node"], ""]
   */
  public function testCreateFromDirectory2(string $recipe_name, string $expected_name, string $expected_type, array $expected_modules, string $expected_description): void {
    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/' . $recipe_name);
    $this->assertSame($expected_name, $recipe->name);
    $this->assertSame($expected_type, $recipe->type);
    $this->assertSame($expected_modules, $recipe->install->modules);
    $this->assertSame($expected_description, $recipe->description);
  }

  public function testCreateFromDirectoryNoRecipe(): void {
    $dir = uniqid('public://');
    mkdir($dir);

    $this->expectException(RecipeFileException::class);
    $this->expectExceptionMessage('There is no ' . $dir . '/recipe.yml file');
    Recipe::createFromDirectory($dir);
  }

  public function testPreExistingDifferentConfiguration(): void {
    // Install the node module, its dependencies and configuration.
    $this->container->get('module_installer')->install(['node']);
    $this->assertFalse($this->config('node.settings')->get('use_admin_theme'), 'The node.settings:use_admin_theme is set to FALSE');

    try {
      Recipe::createFromDirectory('core/tests/fixtures/recipes/install_node_with_config');
      $this->fail('Expected exception not thrown');
    }
    catch (RecipePreExistingConfigException $e) {
      $this->assertSame("The configuration 'node.settings' exists already and does not match the recipe's configuration", $e->getMessage());
      $this->assertSame('node.settings', $e->configName);
    }
  }

  public function testPreExistingMatchingConfiguration(): void {
    // Install the node module, its dependencies and configuration.
    $this->container->get('module_installer')->install(['node']);
    // Change the config to match the recipe's config to prevent the exception
    // being thrown.
    $this->config('node.settings')->set('use_admin_theme', TRUE)->save();

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/install_node_with_config');
    $this->assertSame('core/tests/fixtures/recipes/install_node_with_config/config', $recipe->config->recipeConfigDirectory);
  }

  public function testExampleRecipe(): void {
    // The example recipe imports all the configurations from the node module
    // including optional configurations associated with the search and view
    // modules. So we have to install them before applying the example recipe.
    $this->container->get('module_installer')->install(['search', 'views']);
    // Apply the example recipe.
    $recipe = Recipe::createFromDirectory('core/recipes/example');
    RecipeRunner::processRecipe($recipe);
    // Verify if the 'default_summary_length' value is updated.
    $this->assertSame($this->config('text.settings')->get('default_summary_length'), 700);
  }

}
