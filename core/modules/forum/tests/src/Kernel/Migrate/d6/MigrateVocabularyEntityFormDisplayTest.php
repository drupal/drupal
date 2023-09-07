<?php

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity form display migration.
 *
 * @group forum
 */
class MigrateVocabularyEntityFormDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'forum', 'taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Execute Dependency Migrations.
    $this->migrateContentTypes();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
      'd6_vocabulary_field_instance',
      'd6_vocabulary_entity_display',
      'd6_vocabulary_entity_form_display',
    ]);
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityFormDisplay() {
    $this->assertEntity('node.forum.default', 'node', 'forum');
    $this->assertComponent('node.forum.default', 'taxonomy_forums', 'options_select', 20);
    $this->assertComponent('node.forum.default', 'field_trees', 'options_select', 20);
    $this->assertComponent('node.forum.default', 'field_freetags', 'entity_reference_autocomplete_tags', 20);
  }

  /**
   * Asserts various aspects of a form display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $expected_entity_type
   *   The expected entity type to which the display settings are attached.
   * @param string $expected_bundle
   *   The expected bundle to which the display settings are attached.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $expected_entity_type, string $expected_bundle): void {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = EntityFormDisplay::load($id);
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $entity);
    $this->assertSame($expected_entity_type, $entity->getTargetEntityTypeId());
    $this->assertSame($expected_bundle, $entity->getTargetBundle());
  }

  /**
   * Asserts various aspects of a particular component of a form display.
   *
   * @param string $display_id
   *   The form display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $widget_type
   *   The expected widget type.
   * @param int $weight
   *   The expected weight of the component.
   *
   * @internal
   */
  protected function assertComponent(string $display_id, string $component_id, string $widget_type, int $weight): void {
    $component = EntityFormDisplay::load($display_id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertSame($widget_type, $component['type']);
    $this->assertSame($weight, $component['weight']);
  }

}
