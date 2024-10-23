<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Execute Dependency Migrations.
    $this->migrateContentTypes();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'd6_node_type',
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
    ]);
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyFieldInstance(): void {
    $this->executeMigration('d6_vocabulary_field_instance');

    // Test that the field exists. Tags has a multilingual option of 'None'.
    $field_id = 'node.article.field_tags';
    $field = FieldConfig::load($field_id);
    $this->assertSame($field_id, $field->id(), 'Field instance exists on article bundle.');
    $this->assertSame('Tags', $field->label());
    $this->assertTrue($field->isRequired(), 'Field is required');
    $this->assertFalse($field->isTranslatable());
    $this->assertTargetBundles($field_id, ['tags' => 'tags']);

    // Test the page bundle as well. Tags has a multilingual option of 'None'.
    $field_id = 'node.page.field_tags';
    $field = FieldConfig::load($field_id);
    $this->assertSame($field_id, $field->id(), 'Field instance exists on page bundle.');
    $this->assertSame('Tags', $field->label());
    $this->assertTrue($field->isRequired(), 'Field is required');
    $this->assertFalse($field->isTranslatable());

    $settings = $field->getSettings();
    $this->assertSame('default:taxonomy_term', $settings['handler'], 'The handler plugin ID is correct.');
    $this->assertTargetBundles($field_id, ['tags' => 'tags']);
    $this->assertTrue($settings['handler_settings']['auto_create'], 'The "auto_create" setting is correct.');

    $this->assertSame([['node', 'article', 'field_tags']], $this->getMigration('d6_vocabulary_field_instance')->getIdMap()->lookupDestinationIds([4, 'article']));

    // Test the field vocabulary_1_i_0_ with multilingual option,
    // 'per language terms'.
    $field_id = 'node.story.field_vocabulary_1_i_0_';
    $field = FieldConfig::load($field_id);
    $this->assertFalse($field->isRequired(), 'Field is not required');
    $this->assertTrue($field->isTranslatable());
    $this->assertTargetBundles($field_id, ['vocabulary_1_i_0_' => 'vocabulary_1_i_0_']);

    // Test the field vocabulary_2_i_0_ with multilingual option,
    // 'Set language to vocabulary'.
    $field_id = 'node.story.field_vocabulary_2_i_1_';
    $field = FieldConfig::load($field_id);
    $this->assertFalse($field->isRequired(), 'Field is not required');
    $this->assertFalse($field->isTranslatable());
    $this->assertTargetBundles($field_id, ['vocabulary_2_i_1_' => 'vocabulary_2_i_1_']);

    // Test the field vocabulary_3_i_0_ with multilingual option,
    // 'Localize terms'.
    $field_id = 'node.story.field_vocabulary_3_i_2_';
    $field = FieldConfig::load($field_id);
    $this->assertFalse($field->isRequired(), 'Field is not required');
    $this->assertTrue($field->isTranslatable());
    $this->assertTargetBundles($field_id, ['vocabulary_3_i_2_' => 'vocabulary_3_i_2_']);

    // Tests that a vocabulary named like a D8 base field will be migrated and
    // prefixed with 'field_' to avoid conflicts.
    $field_type = FieldConfig::load('node.sponsor.field_type');
    $this->assertInstanceOf(FieldConfig::class, $field_type);
    $this->assertTrue($field->isTranslatable());
    $this->assertTargetBundles($field_id, ['vocabulary_3_i_2_' => 'vocabulary_3_i_2_']);

    $this->assertTargetBundles('node.employee.field_vocabulary_3_i_2_', ['vocabulary_3_i_2_' => 'vocabulary_3_i_2_']);

  }

  /**
   * Asserts the settings of an entity reference field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string[] $target_bundles
   *   An array of expected target bundles.
   */
  protected function assertTargetBundles($id, array $target_bundles): void {
    $field = FieldConfig::load($id);
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertArrayHasKey('target_bundles', $handler_settings);
    $this->assertSame($handler_settings['target_bundles'], $target_bundles);
  }

  /**
   * Tests that vocabulary field instances are ignored appropriately.
   *
   * Vocabulary field instances should be ignored when they belong to node
   * types which were not migrated.
   */
  public function testSkipNonExistentNodeType(): void {
    // The "story" node type is migrated by d6_node_type but we need to pretend
    // that it didn't occur, so record that in the map table.
    $this->mockFailure('d6_node_type', ['type' => 'story']);

    // d6_vocabulary_field_instance should skip over the "story" node type
    // config because, according to the map table, it didn't occur.
    $migration = $this->getMigration('d6_vocabulary_field_instance');

    $this->executeMigration($migration);
    $this->assertNull($migration->getIdMap()->lookupDestinationIds(['type' => 'story'])[0][0]);
  }

}
