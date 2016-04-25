<?php

namespace Drupal\Tests\taxonomy\Unit\Migrate\d6;

use Drupal\taxonomy\Plugin\migrate\source\d6\TermNode;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests d6_term_node source plugin.
 *
 * @group taxonomy
 */
class TermNodeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = TermNode::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_term_node',
      'vid' => 3,
    ),
  );

  protected $expectedResults = array(
    array(
      'nid' => 1,
      'vid' => 1,
      'type' => 'story',
      'tid' => array(1, 4, 5),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['term_node'] = array(
      array(
        'nid' => '1',
        'vid' => '1',
        'tid' => '1',
      ),
      array(
        'nid' => '1',
        'vid' => '1',
        'tid' => '4',
      ),
      array(
        'nid' => '1',
        'vid' => '1',
        'tid' => '5',
      ),
    );
    $this->databaseContents['node'] = array(
      array(
        'nid' => '1',
        'vid' => '1',
        'type' => 'story',
        'language' => '',
        'title' => 'Test title',
        'uid' => '1',
        'status' => '1',
        'created' => '1388271197',
        'changed' => '1420861423',
        'comment' => '0',
        'promote' => '0',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '0',
        'translate' => '0',
      ),
    );
    $this->databaseContents['term_data'] = array(
      array(
        'tid' => '1',
        'vid' => '3',
        'name' => 'term 1 of vocabulary 3',
        'description' => 'description of term 1 of vocabulary 3',
        'weight' => '0',
      ),
      array(
        'tid' => '4',
        'vid' => '3',
        'name' => 'term 4 of vocabulary 3',
        'description' => 'description of term 4 of vocabulary 3',
        'weight' => '6',
      ),
      array(
        'tid' => '5',
        'vid' => '3',
        'name' => 'term 5 of vocabulary 3',
        'description' => 'description of term 5 of vocabulary 3',
        'weight' => '7',
      ),
    );
    parent::setUp();
  }

}
