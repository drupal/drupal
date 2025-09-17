<?php

declare(strict_types=1);

namespace Drupal\Tests\ban\Kernel\Plugin\migrate\source\d7;

use Drupal\ban\Plugin\migrate\source\d7\BlockedIps;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests D7 blocked_ip source plugin.
 *
 * @legacy-covers \Drupal\ban\Plugin\migrate\source\d7\BlockedIps
 */
#[CoversClass(BlockedIps::class)]
#[Group('ban')]
#[IgnoreDeprecations]
class BlockedIpsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ban', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
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
