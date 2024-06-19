<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;

/**
 * Vocabulary field instance migration.
 *
 * @group forum
 */
class MigrateVocabularyFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'forum',
    'menu_ui',
    'taxonomy',
  ];

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
      'd6_vocabulary_field_instance',
    ]);
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association migration.
   */
  public function testVocabularyFieldInstance(): void {
    $this->assertEntity('node.forum.taxonomy_forums', 'Forums', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.forum.field_trees', 'Trees', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.forum.field_freetags', 'FreeTags', 'entity_reference', FALSE, FALSE);
  }

  /**
   * Asserts various aspects of a field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string $expected_label
   *   The expected field label.
   * @param string $expected_field_type
   *   The expected field type.
   * @param bool $is_required
   *   Whether or not the field is required.
   * @param bool $expected_translatable
   *   Whether or not the field is expected to be translatable.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $expected_label, string $expected_field_type, bool $is_required, bool $expected_translatable): void {
    [$expected_entity_type, $expected_bundle, $expected_name] = explode('.', $id);

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load($id);
    $this->assertInstanceOf(FieldConfigInterface::class, $field);
    $this->assertEquals($expected_label, $field->label());
    $this->assertEquals($expected_field_type, $field->getType());
    $this->assertEquals($expected_entity_type, $field->getTargetEntityTypeId());
    $this->assertEquals($expected_bundle, $field->getTargetBundle());
    $this->assertEquals($expected_name, $field->getName());
    $this->assertEquals($is_required, $field->isRequired());
    $this->assertEquals($expected_entity_type . '.' . $expected_name, $field->getFieldStorageDefinition()->id());
    $this->assertEquals($expected_translatable, $field->isTranslatable());
  }

  /**
   * Asserts the settings of a link field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param int $title_setting
   *   The expected title setting.
   *
   * @internal
   */
  protected function assertLinkFields(string $id, int $title_setting): void {
    $field = FieldConfig::load($id);
    $this->assertSame($title_setting, $field->getSetting('title'));
  }

  /**
   * Asserts the settings of an entity reference field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string[] $target_bundles
   *   An array of expected target bundles.
   *
   * @internal
   */
  protected function assertEntityReferenceFields(string $id, array $target_bundles): void {
    $field = FieldConfig::load($id);
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertArrayHasKey('target_bundles', $handler_settings);
    foreach ($handler_settings['target_bundles'] as $target_bundle) {
      $this->assertContains($target_bundle, $target_bundles);
    }
  }

}
