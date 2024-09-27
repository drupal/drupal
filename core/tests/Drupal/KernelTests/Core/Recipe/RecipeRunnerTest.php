<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipePreExistingConfigException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\views\Entity\View;

/**
 * @coversDefaultClass \Drupal\Core\Recipe\RecipeRunner
 * @group Recipe
 */
class RecipeRunnerTest extends KernelTestBase {

  use RecipeTestTrait;

  public function testModuleInstall(): void {
    // Test the state prior to applying the recipe.
    $this->assertFalse($this->container->get('module_handler')->moduleExists('filter'), 'The filter module is not installed');
    $this->assertFalse($this->container->get('module_handler')->moduleExists('text'), 'The text module is not installed');
    $this->assertFalse($this->container->get('module_handler')->moduleExists('node'), 'The node module is not installed');
    $this->assertFalse($this->container->get('config.storage')->exists('node.settings'), 'The node.settings configuration does not exist');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/install_two_modules');
    RecipeRunner::processRecipe($recipe);

    // Test the state after applying the recipe.
    $this->assertTrue($this->container->get('module_handler')->moduleExists('filter'), 'The filter module is installed');
    $this->assertTrue($this->container->get('module_handler')->moduleExists('text'), 'The text module is installed');
    $this->assertTrue($this->container->get('module_handler')->moduleExists('node'), 'The node module is installed');
    $this->assertTrue($this->container->get('config.storage')->exists('node.settings'), 'The node.settings configuration has been created');
    $this->assertFalse($this->config('node.settings')->get('use_admin_theme'), 'The node.settings:use_admin_theme is set to FALSE');
  }

  public function testModuleAndThemeInstall(): void {
    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/base_theme_and_views');
    RecipeRunner::processRecipe($recipe);

    // Test the state after applying the recipe.
    $this->assertTrue($this->container->get('module_handler')->moduleExists('views'), 'The views module is installed');
    $this->assertTrue($this->container->get('module_handler')->moduleExists('node'), 'The node module is installed');
    $this->assertTrue($this->container->get('theme_handler')->themeExists('test_basetheme'), 'The test_basetheme theme is installed');
    $this->assertTrue($this->container->get('theme_handler')->themeExists('test_subtheme'), 'The test_subtheme theme is installed');
    $this->assertTrue($this->container->get('theme_handler')->themeExists('test_subsubtheme'), 'The test_subsubtheme theme is installed');
    $this->assertTrue($this->container->get('config.storage')->exists('node.settings'), 'The node.settings configuration has been created');
    $this->assertFalse($this->container->get('config.storage')->exists('views.view.archive'), 'The views.view.archive configuration has not been created');
    $this->assertEmpty(View::loadMultiple(), "No views exist");
  }

  public function testThemeModuleDependenciesInstall(): void {
    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/theme_with_module_dependencies');
    RecipeRunner::processRecipe($recipe);

    // Test the state after applying the recipe.
    $this->assertTrue($this->container->get('module_handler')->moduleExists('test_module_required_by_theme'), 'The test_module_required_by_theme module is installed');
    $this->assertTrue($this->container->get('module_handler')->moduleExists('test_another_module_required_by_theme'), 'The test_another_module_required_by_theme module is installed');
    $this->assertTrue($this->container->get('theme_handler')->themeExists('test_theme_depending_on_modules'), 'The test_theme_depending_on_modules theme is installed');
  }

  public function testModuleConfigurationOverride(): void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('node.'), 'There is no node configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/install_node_with_config');
    RecipeRunner::processRecipe($recipe);

    // Test the state after applying the recipe.
    $this->assertTrue($this->container->get('config.storage')->exists('node.settings'), 'The node.settings configuration has been created');
    $this->assertTrue($this->container->get('config.storage')->exists('node.settings'), 'The node.settings configuration has been created');
    $this->assertTrue($this->config('node.settings')->get('use_admin_theme'), 'The node.settings:use_admin_theme is set to TRUE');
    $this->assertSame('Test content type', NodeType::load('test')?->label());
    $node_type_data = $this->config('node.type.test')->get();
    $this->assertGreaterThan(0, strlen($node_type_data['uuid']), 'The node type configuration has been assigned a UUID.');
    // cSpell:disable-next-line
    $this->assertSame('8Jlq8CmNXHVtNIHBHgFGpnAKthlUz0XoW_D0g56QXqY', $node_type_data['_core']['default_config_hash']);
  }

  public function testApplySameRecipe(): void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('node.'), 'There is no node configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/install_node_with_config');
    RecipeRunner::processRecipe($recipe);

    // Test the state prior to applying the recipe.
    $this->assertNotEmpty($this->container->get('config.factory')->listAll('node.'), 'There is node configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/install_node_with_config');
    RecipeRunner::processRecipe($recipe);
    $this->assertTrue(TRUE, 'Applying a recipe for the second time with no config changes results in a successful application');

    $type = NodeType::load('test');
    $type->setNewRevision(FALSE);
    $type->save();

    $this->expectException(RecipePreExistingConfigException::class);
    $this->expectExceptionMessage("The configuration 'node.type.test' exists already and does not match the recipe's configuration");
    Recipe::createFromDirectory('core/tests/fixtures/recipes/install_node_with_config');
  }

  public function testConfigFromModule(): void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is no config_test configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/config_from_module');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $this->assertNotEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is config_test configuration');
    $config_test_entities = \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple();
    $this->assertSame(['dotted.default', 'override'], array_keys($config_test_entities));
  }

  public function testConfigWildcard(): void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is no config_test configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/config_wildcard');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $this->assertNotEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is config_test configuration');
    $config_test_entities = \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple();
    $this->assertSame(['dotted.default', 'override', 'override_unmet'], array_keys($config_test_entities));
    $this->assertSame('Default', $config_test_entities['dotted.default']->label());
    $this->assertSame('herp', $this->config('config_test.system')->get('404'));
  }

  public function testConfigFromModuleAndRecipe(): void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is no config_test configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/config_from_module_and_recipe');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $this->assertNotEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is config_test configuration');
    $config_test_entities = \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple();
    $this->assertSame(['dotted.default', 'override', 'override_unmet'], array_keys($config_test_entities));
    $this->assertSame('Provided by recipe', $config_test_entities['dotted.default']->label());
    $this->assertSame('foo', $this->config('config_test.system')->get('404'));
  }

  public function testRecipeInclude(): void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('node.'), 'There is no node configuration');
    $this->assertFalse($this->container->get('module_handler')->moduleExists('dblog'), 'Dblog module not installed');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/recipe_include');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $this->assertTrue($this->container->get('module_handler')->moduleExists('dblog'), 'Dblog module installed');
    $this->assertSame('Test content type', NodeType::load('test')?->label());
    $this->assertSame('Another test content type', NodeType::load('another_test')?->label());
  }

  public function testConfigActions() :void {
    // Test the state prior to applying the recipe.
    $this->assertEmpty($this->container->get('config.factory')->listAll('config_test.'), 'There is no config_test configuration');

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/config_actions');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');
    $config_test_entity = $storage->load('recipe');
    $this->assertInstanceOf(ConfigTest::class, $config_test_entity);
    $this->assertSame('Created by recipe', $config_test_entity->label());
    $this->assertSame('Set by recipe', $config_test_entity->getProtectedProperty());
    $this->assertSame('not bar', $this->config('config_test.system')->get('foo'));
  }

  public function testConfigActionsPreExistingConfig() :void {
    $this->enableModules(['config_test']);
    $this->installConfig(['config_test']);
    $this->assertSame('bar', $this->config('config_test.system')->get('foo'));
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');
    $config_test_entity = $storage->create(['id' => 'recipe', 'label' => 'Created by test']);
    $this->assertInstanceOf(ConfigTest::class, $config_test_entity);
    $config_test_entity->setProtectedProperty('Set by test');
    $config_test_entity->save();

    $recipe = Recipe::createFromDirectory('core/tests/fixtures/recipes/config_actions');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $config_test_entity = $storage->load('recipe');
    $this->assertInstanceOf(ConfigTest::class, $config_test_entity);
    $this->assertSame('Created by test', $config_test_entity->label());
    $this->assertSame('Set by recipe', $config_test_entity->getProtectedProperty());
    $this->assertSame('not bar', $this->config('config_test.system')->get('foo'));
  }

  public function testInvalidConfigAction() :void {
    $recipe_data = <<<YAML
name: Invalid config action
install:
  - config_test
config:
  actions:
    config_test.system:
      setFoo: 'Bar'
YAML;

    $recipe = $this->createRecipe($recipe_data);
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "setFoo" plugin does not exist.');
    RecipeRunner::processRecipe($recipe);
  }

  public function testInvalidConfigActionAppliedOnConfigEntity() :void {
    $recipe_data = <<<YAML
name: Invalid config action
install:
  - config_test
config:
  actions:
    config_test.dynamic.recipe:
      createIfNotExists:
        label: 'Created by recipe'
      setBody: 'Description set by recipe'
YAML;

    $recipe = $this->createRecipe($recipe_data);
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "config_test" entity does not support the "setBody" config action.');
    RecipeRunner::processRecipe($recipe);
  }

  /**
   * Tests that renamed plugins are marked as deprecated.
   *
   * @group legacy
   */
  public function testRenamedConfigActions(): void {
    $recipe_data = <<<YAML
name: Renamed config action
install:
  - config_test
config:
  actions:
    config_test.dynamic.recipe:
      ensure_exists:
        label: 'Created by recipe'
YAML;
    $recipe = $this->createRecipe($recipe_data);
    $this->expectDeprecation('The plugin ID "entity_create:ensure_exists" is deprecated in drupal:10.3.1 and will be removed in drupal:12.0.0. Use "entity_create:createIfNotExists" instead. See https://www.drupal.org/node/3458273.');
    RecipeRunner::processRecipe($recipe);
  }

  public function testRecipesAreDisambiguatedByPath(): void {
    $recipe_data = <<<YAML
name: 'Recipe include'
recipes:
  - core/tests/fixtures/recipes/recipe_include
install:
  - config_test
YAML;

    $recipe = $this->createRecipe($recipe_data, 'recipe_include');
    RecipeRunner::processRecipe($recipe);

    // Test the state after to applying the recipe.
    $this->assertTrue($this->container->get('module_handler')->moduleExists('dblog'), 'Dblog module installed');
    $this->assertTrue($this->container->get('module_handler')->moduleExists('config_test'), 'Config test module installed');
    $this->assertSame('Test content type', NodeType::load('test')?->label());
    $this->assertSame('Another test content type', NodeType::load('another_test')?->label());

    $operations = RecipeRunner::toBatchOperations($recipe);
    $this->assertSame('triggerEvent', $operations[7][0][1]);
    $this->assertSame('Install node with config', $operations[7][1][0]->name);
    $this->assertStringEndsWith('core/tests/fixtures/recipes/install_node_with_config', $operations[7][1][0]->path);

    $this->assertSame('triggerEvent', $operations[10][0][1]);
    $this->assertSame('Recipe include', $operations[10][1][0]->name);
    $this->assertStringEndsWith('core/tests/fixtures/recipes/recipe_include', $operations[10][1][0]->path);

    $this->assertSame('triggerEvent', $operations[12][0][1]);
    $this->assertSame('Recipe include', $operations[12][1][0]->name);
    $this->assertSame($this->siteDirectory . '/recipes/recipe_include', $operations[12][1][0]->path);
  }

}
