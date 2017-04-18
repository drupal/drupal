<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\NodeType;

/**
 * Upgrade node types to node.type.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeTypeTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
  }

  /**
   * Tests Drupal 6 node type to Drupal 8 migration.
   */
  public function testNodeType() {
    $id_map = $this->getMigration('d6_node_type')->getIdMap();
    // Test the test_page content type.
    $node_type_page = NodeType::load('test_page');
    $this->assertIdentical('test_page', $node_type_page->id(), 'Node type test_page loaded');
    $this->assertIdentical(TRUE, $node_type_page->displaySubmitted());
    $this->assertIdentical(FALSE, $node_type_page->isNewRevision());
    $this->assertIdentical(DRUPAL_OPTIONAL, $node_type_page->getPreviewMode());
    $this->assertIdentical($id_map->lookupDestinationID(['test_page']), ['test_page']);

    // Test we have a body field.
    $field = FieldConfig::loadByName('node', 'test_page', 'body');
    $this->assertIdentical('This is the body field label', $field->getLabel(), 'Body field was found.');

    // Test default menus.
    $expected_available_menus = ['navigation'];
    $this->assertSame($expected_available_menus, $node_type_page->getThirdPartySetting('menu_ui', 'available_menus'));
    $expected_parent = 'navigation:';
    $this->assertSame($expected_parent, $node_type_page->getThirdPartySetting('menu_ui', 'parent'));

    // Test the test_story content type.
    $node_type_story = NodeType::load('test_story');
    $this->assertIdentical('test_story', $node_type_story->id(), 'Node type test_story loaded');

    $this->assertIdentical(TRUE, $node_type_story->displaySubmitted());
    $this->assertIdentical(FALSE, $node_type_story->isNewRevision());
    $this->assertIdentical(DRUPAL_OPTIONAL, $node_type_story->getPreviewMode());
    $this->assertIdentical($id_map->lookupDestinationID(['test_story']), ['test_story']);

    // Test we don't have a body field.
    $field = FieldConfig::loadByName('node', 'test_story', 'body');
    $this->assertIdentical(NULL, $field, 'No body field found');

    // Test default menus.
    $expected_available_menus = ['navigation'];
    $this->assertSame($expected_available_menus, $node_type_story->getThirdPartySetting('menu_ui', 'available_menus'));
    $expected_parent = 'navigation:';
    $this->assertSame($expected_parent, $node_type_story->getThirdPartySetting('menu_ui', 'parent'));

    // Test the test_event content type.
    $node_type_event = NodeType::load('test_event');
    $this->assertIdentical('test_event', $node_type_event->id(), 'Node type test_event loaded');

    $this->assertIdentical(TRUE, $node_type_event->displaySubmitted());
    $this->assertIdentical(TRUE, $node_type_event->isNewRevision());
    $this->assertIdentical(DRUPAL_OPTIONAL, $node_type_event->getPreviewMode());
    $this->assertIdentical($id_map->lookupDestinationID(['test_event']), ['test_event']);

    // Test we have a body field.
    $field = FieldConfig::loadByName('node', 'test_event', 'body');
    $this->assertIdentical('Body', $field->getLabel(), 'Body field was found.');

    $expected_available_menus = ['navigation'];
    $this->assertSame($expected_available_menus, $node_type_event->getThirdPartySetting('menu_ui', 'available_menus'));
    $expected_parent = 'navigation:';
    $this->assertSame($expected_parent, $node_type_event->getThirdPartySetting('menu_ui', 'parent'));
  }

}
