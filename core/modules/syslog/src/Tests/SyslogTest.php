<?php

namespace Drupal\syslog\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests syslog settings.
 *
 * @group syslog
 */
class SyslogTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('syslog');

  /**
   * Tests the syslog settings page.
   */
  function testSettings() {
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);

    // If we're on Windows, there is no configuration form.
    if (defined('LOG_LOCAL6')) {
      $this->drupalPostForm('admin/config/development/logging', array('syslog_facility' => LOG_LOCAL6), t('Save configuration'));
      $this->assertText(t('The configuration options have been saved.'));

      $this->drupalGet('admin/config/development/logging');
      if ($this->parse()) {
        $field = $this->xpath('//option[@value=:value]', array(':value' => LOG_LOCAL6)); // Should be one field.
        $this->assertTrue($field[0]['selected'] == 'selected', 'Facility value saved.');
      }
    }
  }
}
