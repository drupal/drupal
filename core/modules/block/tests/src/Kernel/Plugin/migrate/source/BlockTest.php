<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests block source plugin.
 *
 * @covers \Drupal\block\Plugin\migrate\source\Block
 * @group block
 */
class BlockTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['blocks'] = [
      [
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
      ],
      [
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
      ],
    ];
    $tests[0]['source_data']['blocks_roles'] = [
      [
        'module' => 'block',
        'delta' => 1,
        'rid' => 2,
      ],
      [
        'module' => 'block',
        'delta' => 2,
        'rid' => 2,
      ],
      [
        'module' => 'block',
        'delta' => 2,
        'rid' => 100,
      ],
    ];
    $tests[0]['source_data']['role'] = [
      [
        'rid' => 2,
        'name' => 'authenticated user',
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
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
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
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
        'roles' => [2],
      ],
      [
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
        'roles' => [2],
      ],
    ];
    return $tests;
  }

}
