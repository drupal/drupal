<?php

namespace Drupal\Tests\system\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 theme settings source plugin.
 *
 * @covers Drupal\system\Plugin\migrate\source\d7\ThemeSettings
 *
 * @group system
 */
class ThemeSettingsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $value = [
      'toggle_logo' => 1,
      'toggle_name' => 1,
      'toggle_slogan' => 1,
      'toggle_node_user_picture' => 1,
      'toggle_comment_user_picture' => 1,
      'toggle_comment_user_verification' => 1,
      'toggle_favicon' => 1,
      'toggle_main_menu' => 1,
      'toggle_secondary_menu' => 1,
      'default_logo' => 1,
      'logo_path' => ' ',
      'logo_upload' => ' ',
      'default_favicon' => 1,
      'favicon_path' => ' ',
      'favicon_upload' => ' ',
      'scheme' => 'firehouse',
    ];

    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'theme_olivero_settings',
        'value' => serialize($value),
      ],
    ];

    // The expected results are nearly identical to the source data.
    $tests[0]['expected_data'] = [
      [
        'name' => 'theme_olivero_settings',
        'value' => $value,
      ],
    ];

    return $tests;
  }

}
