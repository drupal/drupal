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

  public static $modules = ['node', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->installEntitySchema('node');
    $this->executeMigrations(['d7_node_type', 'd7_node_title_label']);
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
    $this->assertEntity('node.article.title', 'Title');
    $this->assertEntity('node.blog.title', 'Title');
    $this->assertEntity('node.book.title', 'Title');
    $this->assertEntity('node.forum.title', 'Subject');
    $this->assertEntity('node.page.title', 'Title');
    $this->assertEntity('node.test_content_type.title', 'Title');
  }

}
