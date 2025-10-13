<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel\Plugin\migrate\source\d7;

use Drupal\shortcut\Plugin\migrate\source\d7\ShortcutSet;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests D7 ShortcutSet source plugin.
 */
#[CoversClass(ShortcutSet::class)]
#[Group('shortcut')]
#[RunTestsInSeparateProcesses]
class ShortcutSetTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['shortcut', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['shortcut_set'] = [
      [
        'set_name' => 'shortcut-set-2',
        'title' => 'Alternative shortcut set',
      ],
    ];

    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['shortcut_set'];

    return $tests;
  }

}
