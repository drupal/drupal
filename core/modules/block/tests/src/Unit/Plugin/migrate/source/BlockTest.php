<?php

namespace Drupal\Tests\block\Unit\Plugin\migrate\source;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests block source plugin.
 *
 * @coversDefaultClass \Drupal\block\Plugin\migrate\source\Block
 * @group block
 */
class BlockTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\block\Plugin\migrate\source\Block';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'block',
    ),
  );

  /**
   * Sample block instance query results from the source.
   */
  protected $expectedResults = array(
    array(
      'bid' => 1,
      'module' => 'block',
      'delta' => '1',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 0,
      'region' => 'left',
      'visibility' => 0,
      'pages' => '',
      'title' => 'Test Title 01',
      'cache' => -1,
    ),
    array(
      'bid' => 2,
      'module' => 'block',
      'delta' => '2',
      'theme' => 'garland',
      'status' => 1,
      'weight' => 5,
      'region' => 'right',
      'visibility' => 0,
      'pages' => '<front>',
      'title' => 'Test Title 02',
      'cache' => -1,
    ),
  );

  /**
   * Sample block roles table.
   */
  protected $expectedBlocksRoles = array(
    array(
      'module' => 'block',
      'delta' => 1,
      'rid' => 2,
    ),
  );

  /**
   * Prepopulate database contents.
   */
  protected function setUp() {
    $this->databaseContents['blocks'] = $this->expectedResults;
    $this->databaseContents['blocks_roles'] = $this->expectedBlocksRoles;
    $this->databaseContents['system'] = array(
      array(
        'filename' => 'modules/system/system.module',
        'name' => 'system',
        'type' => 'module',
        'owner' => '',
        'status' => '1',
        'throttle' => '0',
        'bootstrap' => '0',
        'schema_version' => '6055',
        'weight' => '0',
        'info' => 'a:0:{}',
      )
    );
    parent::setUp();
  }

}
