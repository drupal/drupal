<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel\Plugin\migrate\source\d6;

use Drupal\block_content\Plugin\migrate\source\d6\Box;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests D6 block boxes source plugin.
 */
#[CoversClass(Box::class)]
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BoxTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    $tests[0]['source_data']['boxes'] = [
      [
        'bid' => 1,
        'body' => '<p>I made some custom content.</p>',
        'info' => 'Static Block',
        'format' => 1,
      ],
      [
        'bid' => 2,
        'body' => '<p>I made some more custom content.</p>',
        'info' => 'Test Content',
        'format' => 1,
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['boxes'];

    return $tests;
  }

}
