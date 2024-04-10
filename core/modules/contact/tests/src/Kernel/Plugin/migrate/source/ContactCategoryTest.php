<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 contact category source plugin.
 *
 * @covers \Drupal\contact\Plugin\migrate\source\ContactCategory
 * @group contact
 */
class ContactCategoryTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact', 'migrate_drupal', 'user'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'cid' => 1,
        'category' => 'contact category value 1',
        'recipients' => ['admin@example.com', 'user@example.com'],
        'reply' => 'auto reply value 1',
        'weight' => 0,
        'selected' => 0,
      ],
      [
        'cid' => 2,
        'category' => 'contact category value 2',
        'recipients' => ['admin@example.com', 'user@example.com'],
        'reply' => 'auto reply value 2',
        'weight' => 0,
        'selected' => 0,
      ],
    ];

    foreach ($tests[0]['expected_data'] as $k => $row) {
      $row['recipients'] = implode(',', $row['recipients']);
      $tests[0]['source_data']['contact'][$k] = $row;
    }
    return $tests;
  }

}
