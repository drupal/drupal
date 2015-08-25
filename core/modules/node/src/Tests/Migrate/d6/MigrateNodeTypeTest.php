<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeTypeTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\NodeType;

/**
 * Upgrade node types to node.type.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeTypeTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'text', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('node'));
    $this->executeMigration('d6_node_type');
  }

  /**
   * Tests Drupal 6 node type to Drupal 8 migration.
   */
  public function testNodeType() {
    $migration = entity_load('migration', 'd6_node_type');
    // Test the test_page content type.
    $node_type_page = NodeType::load('test_page');
    $this->assertIdentical('test_page', $node_type_page->id(), 'Node type test_page loaded');
    $this->assertIdentical(TRUE, $node_type_page->displaySubmitted());
    $this->assertIdentical(FALSE, $node_type_page->isNewRevision());
    $this->assertIdentical(DRUPAL_OPTIONAL, $node_type_page->getPreviewMode());
    $this->assertIdentical($migration->getIdMap()->lookupDestinationID(array('test_page')), array('test_page'));

    // Test we have a body field.
    $field = FieldConfig::loadByName('node', 'test_page', 'body');
    $this->assertIdentical('This is the body field label', $field->getLabel(), 'Body field was found.');

    // Test the test_story content type.
    $node_type_story = NodeType::load('test_story');
    $this->assertIdentical('test_story', $node_type_story->id(), 'Node type test_story loaded');

    $this->assertIdentical(TRUE, $node_type_story->displaySubmitted());
    $this->assertIdentical(FALSE, $node_type_story->isNewRevision());
    $this->assertIdentical(DRUPAL_OPTIONAL, $node_type_story->getPreviewMode());
    $this->assertIdentical($migration->getIdMap()->lookupDestinationID(array('test_story')), array('test_story'));

    // Test we don't have a body field.
    $field = FieldConfig::loadByName('node', 'test_story', 'body');
    $this->assertIdentical(NULL, $field, 'No body field found');

    // Test the test_event content type.
    $node_type_event = NodeType::load('test_event');
    $this->assertIdentical('test_event', $node_type_event->id(), 'Node type test_event loaded');

    $this->assertIdentical(TRUE, $node_type_event->displaySubmitted());
    $this->assertIdentical(TRUE, $node_type_event->isNewRevision());
    $this->assertIdentical(DRUPAL_OPTIONAL, $node_type_event->getPreviewMode());
    $this->assertIdentical($migration->getIdMap()->lookupDestinationID(array('test_event')), array('test_event'));

    // Test we have a body field.
    $field = FieldConfig::loadByName('node', 'test_event', 'body');
    $this->assertIdentical('Body', $field->getLabel(), 'Body field was found.');
  }

}
