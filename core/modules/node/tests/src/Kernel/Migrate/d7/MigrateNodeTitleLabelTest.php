<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of the title field label for node types.
 *
 * @group node
 */
class MigrateNodeTitleLabelTest extends MigrateDrupal7TestBase {

  public static $modules = ['node', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateContentTypes();
    $this->executeMigration('d7_node_title_label');
  }

  /**
   * Asserts various aspects of a base_field_override entity.
   *
   * @param string $id
   *   The override ID.
   * @param string $label
   *   The label's expected (overridden) value.
   */
  protected function assertEntity($id, $label) {
    $override = BaseFieldOverride::load($id);
    $this->assertTrue($override instanceof BaseFieldOverride);
    /** @var \Drupal\Core\Field\Entity\BaseFieldOverride $override */
    $this->assertIdentical($label, $override->getLabel());
  }

  /**
   * Tests migration of node title field overrides.
   */
  public function testMigration() {
    // Forum title labels are overridden to 'Subject'.
    $this->assertEntity('node.forum.title', 'Subject');
    // Other content types use the default of 'Title' and are not overridden.
    $no_override_node_type = [
      'article',
      'blog',
      'book',
      'page',
      'test_content_type',
    ];
    foreach ($no_override_node_type as $type) {
      $override = BaseFieldOverride::load("node.$type.title");
      $this->assertFalse($override instanceof BaseFieldOverride);
    }
  }

}
