<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTermNodeTestBase.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Base class for Taxonomy/Node migration tests.
 */
abstract class MigrateTermNodeTestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  static $modules = array('node', 'taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'vid' => 'test',
    ));
    $vocabulary->save();
    $node_type = entity_create('node_type', array('type' => 'story'));
    $node_type->save();
    foreach (array('vocabulary_1_i_0_', 'vocabulary_2_i_1_', 'vocabulary_3_i_2_') as $name) {
      entity_create('field_config', array(
        'name' => $name,
        'entity_type' => 'node',
        'type' => 'taxonomy_term_reference',
        'cardinality' => -1,
        'settings' => array(
          'allowed_values' => array(
            array(
              'vocabulary' => $vocabulary->id(),
              'parent' => '0',
            ),
          ),
        ),
      ))->save();
      entity_create('field_instance_config', array(
        'field_name' => $name,
        'entity_type' => 'node',
        'bundle' => 'story',
      ))->save();

    }
    $id_mappings = array(
      'd6_vocabulary_field_instance' => array(
        array(array(1, 'page'), array('node', 'page', 'test')),
      ),
      'd6_vocabulary_entity_display' => array(
        array(array(1, 'page'), array('node', 'page', 'default', 'test')),
      ),
      'd6_vocabulary_entity_form_display' => array(
        array(array(1, 'page'), array('node', 'page', 'default', 'test')),
      ),
      'd6_node' => array(
        array(array(1), array(1)),
        array(array(2), array(2)),
      ),
    );
    $this->prepareIdMappings($id_mappings);

    $vids = array(1, 2, 3);
    for ($i = 1; $i <= 2; $i++) {
      $node = entity_create('node', array(
        'type' => 'story',
        'nid' => $i,
        'vid' => array_shift($vids),
      ));
      $node->enforceIsNew();
      $node->save();
      if ($i == 1) {
        $node->vid->value = array_shift($vids);
        $node->enforceIsNew(FALSE);
        $node->setNewRevision();
        $node->isDefaultRevision(FALSE);
        $node->save();
      }
    }
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Node.php',
      $this->getDumpDirectory() . '/Drupal6TermNode.php',
      $this->getDumpDirectory() . '/Drupal6TaxonomyTerm.php',
      $this->getDumpDirectory() . '/Drupal6TaxonomyVocabulary.php',
    );
    $this->loadDumps($dumps);
  }

}
