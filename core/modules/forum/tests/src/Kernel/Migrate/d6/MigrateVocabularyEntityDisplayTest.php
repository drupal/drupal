<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity display migration.
 *
 * @group forum
 */
class MigrateVocabularyEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'comment',
    'forum',
    'taxonomy',
    'menu_ui',
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
      'd6_vocabulary_entity_display',
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
  public function testVocabularyEntityDisplay(): void {
    $this->assertEntity('node.forum.default');
    $this->assertComponent('node.forum.default', 'taxonomy_forums', 'entity_reference_label', 'hidden', 20);
    $this->assertComponent('node.forum.default', 'field_trees', 'entity_reference_label', 'hidden', 20);
    $this->assertComponent('node.forum.default', 'field_freetags', 'entity_reference_label', 'hidden', 20);
  }

  /**
   * Asserts various aspects of a view display.
   *
   * @param string $id
   *   The view display ID.
   *
   * @internal
   */
  protected function assertEntity(string $id): void {
    $display = EntityViewDisplay::load($id);
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $display);
  }

  /**
   * Asserts various aspects of a particular component of a view display.
   *
   * @param string $display_id
   *   The view display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $type
   *   The expected component type (formatter plugin ID).
   * @param string $label
   *   The expected label of the component.
   * @param int $weight
   *   The expected weight of the component.
   *
   * @internal
   */
  protected function assertComponent(string $display_id, string $component_id, string $type, string $label, int $weight): void {
    $component = EntityViewDisplay::load($display_id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertSame($type, $component['type']);
    $this->assertSame($label, $component['label']);
    $this->assertSame($weight, $component['weight']);
  }

  /**
   * Asserts that a particular component is NOT included in a display.
   *
   * @param string $display_id
   *   The display ID.
   * @param string $component_id
   *   The component ID.
   *
   * @internal
   */
  protected function assertComponentNotExists(string $display_id, string $component_id): void {
    $component = EntityViewDisplay::load($display_id)->getComponent($component_id);
    $this->assertNull($component);
  }

}
