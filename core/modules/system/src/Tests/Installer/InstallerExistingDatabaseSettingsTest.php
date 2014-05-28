<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerExistingDatabaseSettingsTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests the installer to make sure existing values in settings.php appear.
 */
class InstallerExistingDatabaseSettingsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Installer existing database settings',
      'description' => 'Tests the installer with an existing settings file with database connection info.',
      'group' => 'Installer',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Pre-configure database credentials in settings.php.
    $connection_info = Database::getConnectionInfo();
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);

    $this->settings['databases']['default'] = (object) array(
      'value' => $connection_info,
      'required' => TRUE,
    );
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   *
   * @todo The database settings form is not supposed to appear if settings.php
   *   contains a valid database connection already (but e.g. no config
   *   directories yet).
   */
  protected function setUpSettings() {
    // All database settings should be pre-configured, except password.
    $values = $this->parameters['forms']['install_settings_form'];
    $driver = $values['driver'];
    $edit = array();
    if (isset($values[$driver]['password']) && $values[$driver]['password'] !== '') {
      $edit = $this->translatePostValues(array(
        $driver => array(
          'password' => $values[$driver]['password'],
        ),
      ));
    }
    $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
  }

}
