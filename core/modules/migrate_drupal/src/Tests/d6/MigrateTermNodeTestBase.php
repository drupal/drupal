<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTermNodeTestBase.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_reference\Tests\EntityReferenceTestTrait;

/**
 * Base class for Taxonomy/Node migration tests.
 */
abstract class MigrateTermNodeTestBase extends MigrateDrupal6TestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  static $modules = array('node', 'taxonomy', 'text', 'filter', 'entity_reference');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', array('node_access'));

    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'vid' => 'test',
    ));
    $vocabulary->save();
    $node_type = entity_create('node_type', array('type' => 'story'));
    $node_type->save();
    foreach (array('vocabulary_1_i_0_', 'vocabulary_2_i_1_', 'vocabulary_3_i_2_') as $name) {
      $handler_settings = array(
        'target_bundles' => array(
          $vocabulary->id() => $vocabulary->id(),
        ),
        'auto_create' => TRUE,
      );
      $this->createEntityReferenceField('node', 'story', $name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
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
    $this->prepareMigrations($id_mappings);

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
      $this->getDumpDirectory() . '/Node.php',
      $this->getDumpDirectory() . '/NodeRevisions.php',
      $this->getDumpDirectory() . '/ContentTypeStory.php',
      $this->getDumpDirectory() . '/ContentTypeTestPlanet.php',
      $this->getDumpDirectory() . '/TermNode.php',
      $this->getDumpDirectory() . '/TermHierarchy.php',
      $this->getDumpDirectory() . '/TermData.php',
      $this->getDumpDirectory() . '/Vocabulary.php',
      $this->getDumpDirectory() . '/VocabularyNodeTypes.php',
    );
    $this->loadDumps($dumps);
  }

}
