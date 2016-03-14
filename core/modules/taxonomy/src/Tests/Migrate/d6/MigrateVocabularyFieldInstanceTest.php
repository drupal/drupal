<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyFieldInstanceTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy'];

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
  public function testVocabularyFieldInstance() {
    // Test that the field exists.
    $field_id = 'node.article.tags';
    $field = FieldConfig::load($field_id);
    $this->assertIdentical($field_id, $field->id(), 'Field instance exists on article bundle.');
    $this->assertIdentical('Tags', $field->label());
    $this->assertTrue($field->isRequired(), 'Field is required');

    // Test the page bundle as well.
    $field_id = 'node.page.tags';
    $field = FieldConfig::load($field_id);
    $this->assertIdentical($field_id, $field->id(), 'Field instance exists on page bundle.');
    $this->assertIdentical('Tags', $field->label());
    $this->assertTrue($field->isRequired(), 'Field is required');

    $settings = $field->getSettings();
    $this->assertIdentical('default:taxonomy_term', $settings['handler'], 'The handler plugin ID is correct.');
    $this->assertIdentical(['tags'], $settings['handler_settings']['target_bundles'], 'The target_bundles handler setting is correct.');
    $this->assertIdentical(TRUE, $settings['handler_settings']['auto_create'], 'The "auto_create" setting is correct.');

    $this->assertIdentical(array('node', 'article', 'tags'), Migration::load('d6_vocabulary_field_instance')->getIdMap()->lookupDestinationID(array(4, 'article')));

    // Test the the field vocabulary_1_i_0_
    $field_id = 'node.story.vocabulary_1_i_0_';
    $field = FieldConfig::load($field_id);
    $this->assertFalse($field->isRequired(), 'Field is not required');
  }

}
