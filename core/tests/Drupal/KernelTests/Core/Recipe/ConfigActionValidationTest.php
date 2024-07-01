<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Recipe\InvalidConfigException;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeFileException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Recipe
 */
class ConfigActionValidationTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'link',
    'node',
    'shortcut',
    'system',
  ];

  /**
   * {@inheritdoc}
   *
   * This test requires that we save invalid config, so we can test that it gets
   * validated after applying a recipe.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('shortcut');
    $this->installEntitySchema('shortcut');
  }

  /**
   * @testWith ["block_content_type"]
   *   ["node_type"]
   *   ["shortcut_set"]
   *   ["menu"]
   */
  public function testConfigActionsAreValidated(string $entity_type_id): void {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage($entity_type_id);

    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
    $entity_type = $storage->getEntityType();
    // If there is a label key, it's safe to assume that it's not allowed to be
    // empty. We don't care whether it's immutable; we just care that the value
    // the config action sets it to (an empty string) violates config schema.
    $label_key = $entity_type->getKey('label');
    $this->assertNotEmpty($label_key);
    $entity = $storage->create([
      $entity_type->getKey('id') => 'test',
      $label_key => 'Test',
    ]);
    $entity->save();

    $config_name = $entity->getConfigDependencyName();
    $recipe_data = <<<YAML
name: Config actions making bad decisions
config:
  actions:
    $config_name:
      simpleConfigUpdate:
        $label_key: ''
YAML;

    $recipe = $this->createRecipe($recipe_data);
    try {
      RecipeRunner::processRecipe($recipe);
      $this->fail('An exception should have been thrown.');
    }
    catch (InvalidConfigException $e) {
      $this->assertCount(1, $e->violations);
      $violation = $e->violations->get(0);
      $this->assertSame($label_key, $violation->getPropertyPath());
      $this->assertSame("This value should not be blank.", (string) $violation->getMessage());
    }
  }

  /**
   * Tests validating that config actions' dependencies are present.
   *
   * Tests that the all of the config listed in a recipe's config actions are
   * provided by extensions that will be installed by the recipe, or one of its
   * dependencies (no matter how deeply nested).
   *
   * @testWith ["direct_dependency"]
   *   ["indirect_dependency_one_level_down"]
   *   ["indirect_dependency_two_levels_down"]
   */
  public function testConfigActionDependenciesAreValidated(string $name): void {
    Recipe::createFromDirectory("core/tests/fixtures/recipes/config_actions_dependency_validation/$name");
  }

  /**
   * Tests config action validation for missing dependency.
   */
  public function testConfigActionMissingDependency(): void {
    $recipe_data = <<<YAML
name: Config actions making bad decisions
config:
  actions:
    random.config:
      simpleConfigUpdate:
        label: ''
YAML;

    try {
      $this->createRecipe($recipe_data);
      $this->fail('An exception should have been thrown.');
    }
    catch (RecipeFileException $e) {
      $this->assertIsObject($e->violations);
      $this->assertCount(1, $e->violations);
      $this->assertSame('[config][actions][random.config]', $e->violations[0]->getPropertyPath());
      $this->assertSame("Config actions cannot be applied to random.config because the random extension is not installed, and is not installed by this recipe or any of the recipes it depends on.", (string) $e->violations[0]->getMessage());
    }
  }

}
