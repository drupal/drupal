<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\SystemUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrade of system variables.
 */
class SystemUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'System config upgrade test',
      'description' => 'Tests upgrade of system variables to the configuration system.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.system.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of variables to config.
   */
  public function testVariableUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Verify that variables were properly upgraded.
    $expected_config['system.cron'] = array(
      'key' => 'kdm95qppDDlyZrcUOx453YwQqDA4DNmxi4VQcxzFU9M',
      'threshold.autorun' => '86400',
      'threshold.requirements_warning' => '86400',
      'threshold.requirements_error' => '172800',
    );

    $expected_config['system.logging'] = array(
      'error_level' => 'some',
    );

    $expected_config['system.maintenance'] = array(
      'enabled' => '1',
      'message' => 'Testing config upgrade',
    );

    $expected_config['system.performance'] = array(
      'cache.page.enabled' => '1',
      'cache.page.max_age' => '1800',
      'response.gzip' => '1',
      'preprocess.js' => '1',
      'preprocess.css' => '1',
    );

    $expected_config['system.rss'] = array(
      'channel.description' => 'Testing config upgrade',
      'items.limit' => '20',
      'items.view_mode' => 'teaser',
    );

    $expected_config['system.site'] = array(
      'name' => 'Testing config upgrade',
      // The upgrade from site_mail to system.site:mail is not testable as
      // simpletest overrides this configuration with simpletest@example.com.
      // 'mail' => 'config@example.com',
      'slogan' => 'CMI makes Drupal 8 drush cex -y',
      'page.403' => '403',
      'page.404' => '404',
      'page.front' => 'node',
    );

    $expected_config['user.settings'] = array(
      'cancel_method' => 'user_cancel_reassign',
    );

    foreach ($expected_config as $file => $values) {
      $config = config($file);
      $this->verbose(print_r($config->get(), TRUE));
      foreach ($values as $name => $value) {
        $stored = $config->get($name);
        $this->assertEqual($value, $stored, format_string('Expected value for %name found: %stored (previously: %value).', array('%stored' => $stored, '%name' => $name, '%value' => $value)));
      }
    }
  }
}
