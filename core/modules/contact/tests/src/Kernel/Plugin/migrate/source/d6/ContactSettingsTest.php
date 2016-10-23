<?php

namespace Drupal\Tests\contact\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 contact settings source plugin.
 *
 * @covers \Drupal\contact\Plugin\migrate\source\ContactSettings
 * @group contact
 */
class ContactSettingsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contact', 'migrate_drupal', 'user'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'site_name',
        'value' => serialize('Blorf!'),
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
      ]
    ];
    $tests[0]['expected_data'] = [
      [
        'default_category' => '1',
        'site_name' => 'Blorf!',
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration']['variables'] = ['site_name'];

    return $tests;
  }

}
