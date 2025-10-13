<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Kernel\Plugin\migrate\source\d6;

use Drupal\contact\Plugin\migrate\source\ContactSettings;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests D6 contact settings source plugin.
 */
#[CoversClass(ContactSettings::class)]
#[Group('contact')]
#[RunTestsInSeparateProcesses]
class ContactSettingsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact', 'migrate_drupal', 'user'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'site_name',
        'value' => serialize('foo!'),
      ],
    ];
    $tests[0]['source_data']['contact'] = [
      [
        'cid' => '1',
        'category' => 'Website feedback',
        'recipients' => 'admin@example.com',
        'reply' => '',
        'weight' => '0',
        'selected' => '1',
      ],
    ];
    $tests[0]['expected_data'] = [
      [
        'default_category' => '1',
        'site_name' => 'foo!',
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration']['variables'] = ['site_name'];

    return $tests;
  }

}
