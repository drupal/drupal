<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyUpgradePathTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\system\Tests\Upgrade\UpgradePathTestBase;

/**
 * Tests upgrade of taxonomy variables.
 */
class TaxonomyUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy upgrade test',
      'description' => 'Tests upgrade of Taxonomy module.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'taxonomy') . '/tests/upgrade/drupal-7.taxonomy.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of taxonomy_term_reference field default values.
   */
  public function testEntityDisplayUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the configuration entries were created.
    $config_entity = \Drupal::config('field.instance.node.article.field_tags')->get();
    $this->assertTrue(!empty($config_entity), 'Config entity has been created');
    $this->assertTrue(!empty($config_entity['default_value'][0]['target_uuid']), 'Default value contains target_uuid property');

    // Load taxonomy term to check UUID conversion.
    $taxonomy_term = entity_load('taxonomy_term', 2);

    // Check that default_value has been converted to Drupal 8 structure.
    $this->assertEqual($taxonomy_term->uuid(), $config_entity['default_value'][0]['target_uuid'], 'Default value contains the right target_uuid');
    $this->assertEqual('', $config_entity['default_value'][0]['revision_id'], 'Default value contains the right revision_id');
  }

}
