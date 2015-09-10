<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d7\MigrateNodeTaxonomyTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d7;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * @group taxonomy
 */
class MigrateNodeTaxonomyTest extends MigrateDrupal7TestBase {

  public static $modules = array(
    'datetime',
    'entity_reference',
    'field',
    'filter',
    'image',
    'link',
    'node',
    'taxonomy',
    'telephone',
    'text',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);

    $this->executeMigration('d7_node_type');

    FieldStorageConfig::create(array(
      'type' => 'entity_reference',
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
      'cardinality' => FieldStorageConfigInterface::CARDINALITY_UNLIMITED,
    ))->save();

    FieldConfig::create(array(
      'entity_type' => 'node',
      'field_name' => 'field_tags',
      'bundle' => 'article',
    ))->save();

    $this->executeMigration('d7_taxonomy_vocabulary');
    $this->executeMigration('d7_taxonomy_term');
    $this->executeMigration('d7_user_role');
    $this->executeMigration('d7_user');
    $this->executeMigration('d7_node__article');
  }

  /**
   * Test node migration from Drupal 7 to 8.
   */
  public function testMigration() {
    $node = Node::load(2);
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertEqual(9, $node->field_tags[0]->target_id);
    $this->assertEqual(14, $node->field_tags[1]->target_id);
    $this->assertEqual(17, $node->field_tags[2]->target_id);
  }

}
