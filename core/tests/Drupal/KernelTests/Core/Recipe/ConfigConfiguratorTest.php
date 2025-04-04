<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipePreExistingConfigException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use org\bovigo\vfs\vfsStream;

/**
 * @covers \Drupal\Core\Recipe\ConfigConfigurator
 * @group Recipe
 */
class ConfigConfiguratorTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * Tests creating an existing configuration with a difference key order.
   */
  public function testExistingConfigWithKeysInDifferentOrder(): void {
    $recipe_dir = uniqid('public://recipe_test_');
    mkdir($recipe_dir . '/config', recursive: TRUE);

    $this->enableModules(['system']);
    $this->installConfig('system');
    /** @var mixed[][] $original_data */
    $original_data = $this->config('system.site')->get();
    // Remove keys that are ignored during the comparison.
    unset($original_data['uuid'], $original_data['_core']);
    $recipe_data = $original_data;
    // Reorder an inner array, to ensure keys are sorted recursively.
    $recipe_data['page'] = array_reverse($original_data['page'], TRUE);
    $this->assertNotSame($original_data, $recipe_data);
    file_put_contents($recipe_dir . '/config/system.site.yml', Yaml::encode($recipe_data));

    $recipe = [
      'name' => 'Same config, different order',
      'type' => 'Testing',
    ];
    file_put_contents($recipe_dir . '/recipe.yml', Yaml::encode($recipe));

    // If there was a conflict with the pre-existing config, ConfigConfigurator
    // would throw an exception and the recipe would not be created. So all we
    // need to do here is assert that, in fact, we were able to create a recipe
    // object.
    $this->assertInstanceOf(Recipe::class, Recipe::createFromDirectory($recipe_dir));
  }

  /**
   * @testWith [false]
   *   [[]]
   */
  public function testExistingConfigIsIgnoredInLenientMode(array|false $strict_value): void {
    $recipe = Recipe::createFromDirectory('core/recipes/page_content_type');
    $this->assertNotEmpty($recipe->config->getConfigStorage()->listAll());
    RecipeRunner::processRecipe($recipe);

    // Clone the recipe into the virtual file system, and opt the clone into
    // lenient mode.
    $recipe_dir = $this->cloneRecipe($recipe->path);
    $this->alterRecipe($recipe_dir, function (array $data) use ($strict_value): array {
      $data['config']['strict'] = $strict_value;
      return $data;
    });
    // The recipe should not have any config to install; all of it already
    // exists.
    $recipe = Recipe::createFromDirectory($recipe_dir);
    $this->assertEmpty($recipe->config->getConfigStorage()->listAll());
  }

  /**
   * Tests with strict mode on part of the configuration.
   */
  public function testSelectiveStrictness(): void {
    $recipe = Recipe::createFromDirectory('core/recipes/page_content_type');
    RecipeRunner::processRecipe($recipe);

    $node_type = NodeType::load('page');
    $original_description = $node_type->getDescription();
    $node_type->set('description', 'And now for something completely different.')
      ->save();

    $form_display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getFormDisplay('node', 'page');
    $this->assertFalse($form_display->isNew());
    $this->assertIsArray($form_display->getComponent('uid'));
    $form_display->removeComponent('uid')->save();

    // Delete something that the recipe provides, so we can be sure it is
    // recreated if it's not in the strict list.
    BaseFieldOverride::loadByName('node', 'page', 'promote')->delete();

    // Clone the recipe into the virtual file system, and opt only the node
    // type into strict mode.
    $clone_dir = $this->cloneRecipe($recipe->path);
    $this->alterRecipe($clone_dir, function (array $data): array {
      $data['config']['strict'] = ['node.type.page'];
      return $data;
    });
    // If we try to instantiate this recipe, we should an exception.
    try {
      Recipe::createFromDirectory($clone_dir);
      $this->fail('Expected an exception but none was thrown.');
    }
    catch (RecipePreExistingConfigException $e) {
      $this->assertSame("The configuration 'node.type.page' exists already and does not match the recipe's configuration", $e->getMessage());
    }

    // If we restore the node type's original description, we should no longer
    // get an error if we try to instantiate the altered recipe, even though the
    // form display is still different from what's in the recipe.
    NodeType::load('page')
      ->set('description', $original_description)
      ->save();

    $recipe = Recipe::createFromDirectory($clone_dir);
    RecipeRunner::processRecipe($recipe);

    // Make certain that our change to the form display is still there.
    $component = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getFormDisplay('node', 'page')
      ->getComponent('uid');
    $this->assertNull($component);

    // The thing we deleted should have been recreated.
    $this->assertInstanceOf(BaseFieldOverride::class, BaseFieldOverride::loadByName('node', 'page', 'promote'));
  }

  /**
   * Tests strict mode.
   */
  public function testFullStrictness(): void {
    $recipe = Recipe::createFromDirectory('core/recipes/page_content_type');
    RecipeRunner::processRecipe($recipe);

    NodeType::load('page')
      ->set('description', 'And now for something completely different.')
      ->save();

    // Clone the recipe into the virtual file system, and opt all of its config
    // into strict mode.
    $clone_dir = $this->cloneRecipe($recipe->path);
    $this->alterRecipe($clone_dir, function (array $data): array {
      $data['config']['strict'] = TRUE;
      return $data;
    });
    // If we try to instantiate this recipe, we should an exception.
    $this->expectException(RecipePreExistingConfigException::class);
    $this->expectExceptionMessage("The configuration 'node.type.page' exists already and does not match the recipe's configuration");
    Recipe::createFromDirectory($clone_dir);
  }

  /**
   * Clones a recipes.
   */
  private function cloneRecipe(string $original_dir): string {
    // Clone the recipe into the virtual file system.
    $name = uniqid();
    $clone_dir = $this->vfsRoot->url() . '/' . $name;
    mkdir($clone_dir);
    $clone_dir = $this->vfsRoot->getChild($name);
    vfsStream::copyFromFileSystem($original_dir, $clone_dir);
    return $clone_dir->url();
  }

}
