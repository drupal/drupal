<?php

namespace Drupal\Tests\ban\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 blocked_ip source plugin.
 *
 * @covers \Drupal\ban\Plugin\migrate\source\d7\BlockedIps
 * @group ban
 */
class BlockedIpsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ban', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0]['source_data']['blocked_ips'] = [
      [
        'iid' => 1,
        'ip' => '127.0.0.1',
      ],
    ];
    $tests[0]['expected_data'] = [
      [
        'ip' => '127.0.0.1',
      ],
    ];
    return $tests;
  }

}
