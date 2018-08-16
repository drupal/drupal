<?php

namespace Drupal\Tests\tracker\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 tracker user source plugin.
 *
 * @covers Drupal\tracker\Plugin\migrate\source\d7\TrackerUser
 *
 * @group tracker
 */
class TrackerUserTest extends MigrateSqlSourceTestBase {

  /**
  * {@inheritdoc}
  */
  public static $modules = ['tracker', 'migrate_drupal'];

  /**
  * {@inheritdoc}
  */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['database']['tracker_user'] = [
      [
        'nid' => '1',
        'uid' => '2',
        'published' => '1',
        'changed' => '1421727536',
      ],
    ];

    // The expected results are identical to the source data.
    $tests[0]['expected_results'] = $tests[0]['database']['tracker_user'];

    return $tests;
  }

}
