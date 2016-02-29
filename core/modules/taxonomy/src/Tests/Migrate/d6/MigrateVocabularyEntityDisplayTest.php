<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyEntityDisplayTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity display migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateTaxonomy();
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityDisplay() {
    // Test that the field exists.
    $component = EntityViewDisplay::load('node.page.default')->getComponent('tags');
    $this->assertIdentical('entity_reference_label', $component['type']);
    $this->assertIdentical(20, $component['weight']);
    // Test the Id map.
    $this->assertIdentical(array('node', 'article', 'default', 'tags'), Migration::load('d6_vocabulary_entity_display')->getIdMap()->lookupDestinationID(array(4, 'article')));
  }

}
