<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Recipe\InvalidConfigException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests config actions targeting multiple entities using wildcards.
 *
 * @covers \Drupal\Core\Config\Action\Plugin\ConfigAction\CreateForEachBundle
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

    $this->createContentType(['type' => 'one', 'name' => 'Type A']);
    $this->createContentType(['type' => 'two', 'name' => 'Type B']);

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
      simpleConfigUpdate:
        label: 'Changed by config action'
YAML;
    $recipe = $this->createRecipe($contents);

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage($expected_exception_message);
    RecipeRunner::processRecipe($recipe);
  }

  /**
   * Tests that the createForEach action works as expected in normal conditions.
   */
  public function testCreateForEach(): void {
    $this->enableModules(['image', 'language']);

    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $manager->applyAction('createForEach', 'node.type.*', [
      'language.content_settings.node.%bundle' => [
        'target_entity_type_id' => 'node',
        'target_bundle' => '%bundle',
      ],
    ]);
    $this->assertIsObject(ContentLanguageSettings::load('node.one'));
    $this->assertIsObject(ContentLanguageSettings::load('node.two'));
  }

  /**
   * Tests that the createForEach action validates the config it creates.
   */
  public function testCreateForEachValidatesCreatedEntities(): void {
    $this->enableModules(['image']);

    // To prove that the validation runs, we need to disable strict schema
    // checking in this test. We need to explicitly unsubscribe it from events
    // because by this point in the test it has been fully wired up into the
    // container and can't be changed.
    $schema_checker = $this->container->get('testing.config_schema_checker');
    $this->container->get(EventDispatcherInterface::class)
      ->removeSubscriber($schema_checker);

    try {
      $this->container->get('plugin.manager.config_action')
        ->applyAction('createForEach', 'node.type.*', [
          'image.style.node__%bundle' => [],
        ]);
      $this->fail('Expected an exception to be thrown but it was not.');
    }
    catch (InvalidConfigException $e) {
      $this->assertSame('image.style.node__one', $e->data->getName());
      $this->assertCount(1, $e->violations);
      $this->assertSame('label', $e->violations[0]->getPropertyPath());
      $this->assertSame(NotNull::IS_NULL_ERROR, $e->violations[0]->getCode());
    }
  }

  /**
   * Tests using the `%label` placeholder with the createForEach action.
   */
  public function testCreateForEachWithLabel(): void {
    $this->enableModules(['image']);

    // We should be able to use the `%label` placeholder.
    $this->container->get('plugin.manager.config_action')
      ->applyAction('createForEach', 'node.type.*', [
        'image.style.node_%bundle_big' => [
          'label' => 'Big image for %label content',
        ],
      ]);
    $this->assertSame('Big image for Type A content', ImageStyle::load('node_one_big')?->label());
    $this->assertSame('Big image for Type B content', ImageStyle::load('node_two_big')?->label());
  }

  /**
   * Tests that the createForEachIfNotExists action ignores existing config.
   */
  public function testCreateForEachIfNotExists(): void {
    $this->enableModules(['language']);

    ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'one',
    ])->save();

    $this->container->get('plugin.manager.config_action')
      ->applyAction('createForEachIfNotExists', 'node.type.*', [
        'language.content_settings.node.%bundle' => [
          'target_entity_type_id' => 'node',
          'target_bundle' => '%bundle',
        ],
      ]);
    $this->assertIsObject(ContentLanguageSettings::loadByEntityTypeBundle('node', 'two'));
  }

  /**
   * Tests that the createForEach action errs on conflict with existing config.
   */
  public function testCreateForEachErrorsIfAlreadyExists(): void {
    $this->enableModules(['language']);

    ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'one',
    ])->save();

    $this->expectExceptionMessage(ConfigActionException::class);
    $this->expectExceptionMessage('Entity language.content_settings.node.one exists');
    $this->container->get('plugin.manager.config_action')
      ->applyAction('createForEach', 'node.type.*', [
        'language.content_settings.node.%bundle' => [
          'target_entity_type_id' => 'node',
          'target_bundle' => '%bundle',
        ],
      ]);
  }

  /**
   * Tests that the createForEach action only works on bundle entities.
   */
  public function testCreateForEachNotAvailableOnNonBundleEntities(): void {
    $this->enableModules(['language']);

    // We should not be able to use this action on entities that aren't
    // themselves bundles of another entity type.
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "language_content_settings" entity does not support the "createForEach" config action.');
    $this->container->get('plugin.manager.config_action')
      ->applyAction('createForEach', 'language.content_settings.node.*', []);
  }

  /**
   * Tests that the createForEach action requires bundle entity types to exist.
   */
  public function testCreateForEachErrorsIfNoBundleEntityTypesExist(): void {
    $this->disableModules(['node', 'entity_test']);

    $manager = $this->container->get('plugin.manager.config_action');
    $manager->clearCachedDefinitions();
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The create_for_each_bundle:createForEach config action must be restricted to entity types that are bundles of another entity type.');
    $manager->applyAction('create_for_each_bundle:createForEach', 'node.type.*', []);
  }

}
