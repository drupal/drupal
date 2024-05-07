<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests config actions targeting multiple entities using wildcards.
 *
 * @group Recipe
 */
class WildcardConfigActionsTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('node');

    $this->createContentType(['type' => 'one']);
    $this->createContentType(['type' => 'two']);

    EntityTestBundle::create(['id' => 'one'])->save();
    EntityTestBundle::create(['id' => 'two'])->save();

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test_with_bundle',
      'field_name' => 'field_test',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    FieldConfig::create(['field_storage' => $field_storage, 'bundle' => 'one'])
      ->save();
    FieldConfig::create(['field_storage' => $field_storage, 'bundle' => 'two'])
      ->save();

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    FieldConfig::create(['field_storage' => $field_storage, 'bundle' => 'one'])
      ->save();
    FieldConfig::create(['field_storage' => $field_storage, 'bundle' => 'two'])
      ->save();
  }

  /**
   * Tests targeting multiple config entities for an action, using wildcards.
   *
   * @param string $expression
   *   The expression the recipe will use to target multiple config entities.
   * @param string[] $expected_changed_entities
   *   The IDs of the config entities that we expect the recipe to change.
   *
   * @testWith ["field.field.node.one.*", ["node.one.body", "node.one.field_test"]]
   *   ["field.field.node.*.body", ["node.one.body", "node.two.body"]]
   *   ["field.field.*.one.field_test", ["entity_test_with_bundle.one.field_test", "node.one.field_test"]]
   *   ["field.field.node.*.*", ["node.one.body", "node.one.field_test", "node.two.body", "node.two.field_test"]]
   *   ["field.field.*.one.*", ["entity_test_with_bundle.one.field_test", "node.one.field_test", "node.one.body"]]
   *   ["field.field.*.*.field_test", ["entity_test_with_bundle.one.field_test", "entity_test_with_bundle.two.field_test", "node.one.field_test", "node.two.field_test"]]
   *   ["field.field.*.*.*", ["entity_test_with_bundle.one.field_test", "entity_test_with_bundle.two.field_test", "node.one.field_test", "node.two.field_test", "node.one.body", "node.two.body"]]
   */
  public function testTargetEntitiesByWildcards(string $expression, array $expected_changed_entities): void {
    $contents = <<<YAML
name: 'Wildcards!'
config:
  actions:
    $expression:
      setLabel: 'Changed by config action'
YAML;

    $recipe = $this->createRecipe($contents);
    RecipeRunner::processRecipe($recipe);

    $changed = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('field_config')
      ->getQuery()
      ->condition('label', 'Changed by config action')
      ->execute();
    sort($expected_changed_entities);
    sort($changed);
    $this->assertSame($expected_changed_entities, array_values($changed));
  }

  /**
   * Tests that an invalid wildcard expression will raise an error.
   *
   * @testWith ["field.*.node.one.*", "No installed config entity type uses the prefix in the expression 'field.*.node.one.*'. Either there is a typo in the expression or this recipe should install an additional module or depend on another recipe."]
   *   ["field.field.node.*.body/", " could not be parsed."]
   */
  public function testInvalidExpression(string $expression, string $expected_exception_message): void {
    $contents = <<<YAML
name: 'Wildcards gone wild...'
config:
  actions:
    $expression:
      simple_config_update:
        label: 'Changed by config action'
YAML;
    $recipe = $this->createRecipe($contents);

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage($expected_exception_message);
    RecipeRunner::processRecipe($recipe);
  }

}
