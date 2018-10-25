<?php

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the variable source plugin.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\d7\VariableTranslation
 *
 * @group migrate_drupal
 */
class VariableTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['variable_store'] = [
      [
        'realm' => 'language',
        'realm_key' => 'fr',
        'name' => 'site_slogan',
        'value' => 'fr - site slogan',
        'serialized' => '0',
      ],
      [
        'realm' => 'language',
        'realm_key' => 'fr',
        'name' => 'user_mail_status_blocked_subject',
        'value' => 'fr - BEGONE!',
        'serialized' => '0',
      ],
      [
        'realm' => 'language',
        'realm_key' => 'is',
        'name' => 'site_slogan',
        'value' => 's:16:"is - site slogan";',
        'serialized' => '1',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'language' => 'fr',
        'site_slogan' => 'fr - site slogan',
        'user_mail_status_blocked_subject' => 'fr - BEGONE!',
      ],
      [
        'language' => 'is',
        'site_slogan' => 'is - site slogan',
      ],
    ];

    // The expected count.
    $tests[0]['expected_count'] = NULL;

    // The migration configuration.
    $tests[0]['configuration']['variables'] = [
      'site_slogan',
      'user_mail_status_blocked_subject',
    ];

    return $tests;
  }

}
