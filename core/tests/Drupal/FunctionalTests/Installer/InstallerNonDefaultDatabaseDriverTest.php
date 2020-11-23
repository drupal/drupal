<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;

/**
 * Tests the interactive installer.
 *
 * @group Installer
 */
class InstallerNonDefaultDatabaseDriverTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the test database driver in use.
   * @var string
   */
  protected $testDriverName;

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    $driver = Database::getConnection()->driver();
    if (!in_array($driver, ['mysql', 'pgsql'])) {
      $this->markTestSkipped("This test does not support the {$driver} database driver.");
    }
    $this->testDriverName = 'Drivertest' . ucfirst($driver);

    // Assert that we are using the database drivers from the driver_test module.
    $elements = $this->xpath('//label[@for="edit-driver-drivertestmysql"]');
    $this->assertEqual(current($elements)->getText(), 'MySQL by the driver_test module');
    $elements = $this->xpath('//label[@for="edit-driver-drivertestpgsql"]');
    $this->assertEqual(current($elements)->getText(), 'PostgreSQL by the driver_test module');

    $settings = $this->parameters['forms']['install_settings_form'];

    $settings['driver'] = $this->testDriverName;
    $settings[$this->testDriverName] = $settings[$driver];
    unset($settings[$driver]);
    $edit = $this->translatePostValues($settings);
    $this->submitForm($edit, $this->translations['Save and continue']);
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that in the settings.php the database connection array has the
    // correct values set.
    $contents = file_get_contents($this->container->getParameter('app.root') . '/' . $this->siteDirectory . '/settings.php');
    $this->assertStringContainsString("'namespace' => 'Drupal\\\\driver_test\\\\Driver\\\\Database\\\\{$this->testDriverName}',", $contents);
    $this->assertStringContainsString("'driver' => '{$this->testDriverName}',", $contents);
    $this->assertStringContainsString("'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/{$this->testDriverName}/',", $contents);
  }

}
