<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests deprecation of the non-interactive installer with driver name.
 *
 * @group Installer
 * @group legacy
 */
class InstallerDeprecatedDriverNameTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Execute the non-interactive installer.
   *
   * @see install_drupal()
   */
  protected function doInstall() {
    require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
    $parameters = $this->installParameters();
    // Replace the driver namespace with the driver name in the
    // 'install_settings_form' parameter.
    $driverNamespace = $parameters['forms']['install_settings_form']['driver'];
    $driverName = Database::getDriverList()->get($driverNamespace)->getDriverName();
    $parameters['forms']['install_settings_form']['driver'] = $driverName;
    $parameters['forms']['install_settings_form'][$driverName] = $parameters['forms']['install_settings_form'][$driverNamespace];
    unset($parameters['forms']['install_settings_form'][$driverNamespace]);
    // Simulate a real install which does not start with the any connections set
    // in \Drupal\Core\Database\Database::$connections.
    Database::removeConnection('default');
    $this->expectDeprecation("Passing a database driver name '{$driverName}' to install_get_form() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Pass a database driver namespace instead. See https://www.drupal.org/node/3258175");
    $this->expectDeprecation('Drupal\\Core\\Extension\\DatabaseDriverList::getFromDriverName() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use DatabaseDriverList::get() instead, passing a database driver namespace. See https://www.drupal.org/node/3258175');
    install_drupal($this->classLoader, $parameters);
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertSession()->addressEquals('/');
    $this->assertSession()->statusCodeEquals(200);
  }

}
