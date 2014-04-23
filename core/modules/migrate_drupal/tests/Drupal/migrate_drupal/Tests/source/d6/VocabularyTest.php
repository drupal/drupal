<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\VocabularyTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 vocabulary source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class VocabularyTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Vocabulary';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_vocabulary',
    ),
  );

  protected $expectedResults = array(
    array(
      'vid' => 1,
      'name' => 'Tags',
      'description' => 'Tags description.',
      'help' => 1,
      'relations' => 0,
      'hierarchy' => 0,
      'multiple' => 0,
      'required' => 0,
      'tags' => 1,
      'module' => 'taxonomy',
      'weight' => 0,
      'node_types' => array('page', 'article'),
    ),
    array(
      'vid' => 2,
      'name' => 'Categories',
      'description' => 'Categories description.',
      'help' => 1,
      'relations' => 1,
      'hierarchy' => 1,
      'multiple' => 0,
      'required' => 1,
      'tags' => 0,
      'module' => 'taxonomy',
      'weight' => 0,
      'node_types' => array('article'),
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 vocabulary source functionality',
      'description' => 'Tests D6 vocabulary source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $row) {
      foreach ($row['node_types'] as $type) {
        $this->databaseContents['vocabulary_node_types'][] = array(
          'type' => $type,
          'vid' => $row['vid'],
        );
      }
      unset($row['node_types']);
    }
    $this->databaseContents['vocabulary'] = $this->expectedResults;
    parent::setUp();
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\Vocabulary;

class TestVocabulary extends Vocabulary {

  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

}
