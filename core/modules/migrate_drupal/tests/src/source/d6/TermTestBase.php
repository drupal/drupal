<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\TermTestBase.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Base class for the term source tests.
 */
class TermTestBase extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\Term';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highwaterProperty' => array('field' => 'test'),
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_taxonomy_term',
    ),
  );

  protected $expectedResults = array(
    array(
      'tid' => 1,
      'vid' => 5,
      'name' => 'name value 1',
      'description' => 'description value 1',
      'weight' => 0,
      'parent' => array(0),
    ),
    array(
      'tid' => 2,
      'vid' => 6,
      'name' => 'name value 2',
      'description' => 'description value 2',
      'weight' => 0,
      'parent' => array(0),
    ),
    array(
      'tid' => 3,
      'vid' => 6,
      'name' => 'name value 3',
      'description' => 'description value 3',
      'weight' => 0,
      'parent' => array(0),
    ),
    array(
      'tid' => 4,
      'vid' => 5,
      'name' => 'name value 4',
      'description' => 'description value 4',
      'weight' => 1,
      'parent' => array(1),
    ),
    array(
      'tid' => 5,
      'vid' => 6,
      'name' => 'name value 5',
      'description' => 'description value 5',
      'weight' => 1,
      'parent' => array(2),
    ),
    array(
      'tid' => 6,
      'vid' => 6,
      'name' => 'name value 6',
      'description' => 'description value 6',
      'weight' => 0,
      'parent' => array(3, 2),
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 taxonomy term source functionality',
      'description' => 'Tests D6 taxonomy term source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      foreach ($row['parent'] as $parent) {
        $this->databaseContents['term_hierarchy'][] = array(
          'tid' => $row['tid'],
          'parent' => $parent,
        );
      }
      unset($row['parent']);
      $this->databaseContents['term_data'][$k] = $row;
    }
    parent::setUp();
  }

}
