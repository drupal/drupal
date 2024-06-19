<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Test\PerformanceTestRecorder;
use Drupal\Core\Extension\ModuleUninstallValidatorException;

// cspell:ignore drupalmysqldriverdatabasemysql drupalpgsqldriverdatabasepgsql

/**
 * Tests the interactive installer.
 *
 * @group Installer
 */
class InstallerTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller(): void {
    $this->assertNotEquals('0', \Drupal::service('asset.query_string')->get(), 'The dummy query string should be set during install');
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());

    // Verify that the confirmation message appears.
    require_once $this->root . '/core/includes/install.inc';
    $this->assertSession()->pageTextContains('Congratulations, you installed Drupal!');

    // Ensure that the timezone is correct for sites under test after installing
    // interactively.
    $this->assertEquals('Australia/Sydney', $this->config('system.date')->get('timezone.default'));

    // Ensure the profile has a weight of 1000.
    $module_extension_list = \Drupal::service('extension.list.module');
    $extensions = $module_extension_list->getList();

    $this->assertArrayHasKey('testing', $extensions);
    $this->assertEquals(1000, $extensions['testing']->weight);
    // Ensures that router is not rebuilt unnecessarily during the install.
    $this->assertSame(1, \Drupal::service('core.performance.test.recorder')->getCount('event', RoutingEvents::FINISHED));
  }

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage() {
    // Test that \Drupal\Core\Render\BareHtmlPageRenderer adds assets and
    // metatags as expected to the first page of the installer.
    $this->assertSession()->responseContains("css/components/button.css");
    $this->assertSession()->responseContains('<meta charset="utf-8" />');

    // Assert that the expected title is present.
    $this->assertEquals('Choose language', $this->cssSelect('main h2')[0]->getText());

    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    $settings_services_file = DRUPAL_ROOT . '/sites/default/default.services.yml';
    // Copy the testing-specific service overrides in place.
    copy($settings_services_file, $this->siteDirectory . '/services.yml');
    PerformanceTestRecorder::registerService($this->siteDirectory . '/services.yml', TRUE);
    // Assert that the expected title is present.
    $this->assertEquals('Select an installation profile', $this->cssSelect('main h2')[0]->getText());
    // Verify that Title/Label are not displayed when '#title_display' =>
    // 'invisible' attribute is set.
    $this->assertSession()->elementsCount('xpath', "//span[contains(@class, 'visually-hidden') and contains(text(), 'Select an installation profile')]", 1);

    parent::setUpProfile();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // Assert that the expected title is present.
    $this->assertEquals('Database configuration', $this->cssSelect('main h2')[0]->getText());

    // Assert that we use the by core supported database drivers by default and
    // not the ones from the driver_test module.
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-driver-drupalmysqldriverdatabasemysql"]', 'MySQL, MariaDB, Percona Server, or equivalent');
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-driver-drupalpgsqldriverdatabasepgsql"]', 'PostgreSQL');

    parent::setUpSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // Assert that the expected title is present.
    $this->assertEquals('Configure site', $this->cssSelect('main h2')[0]->getText());

    // Test that SiteConfigureForm::buildForm() has made the site directory and
    // the settings file non-writable.
    $site_directory = $this->container->getParameter('app.root') . '/' . $this->siteDirectory;
    $this->assertDirectoryIsNotWritable($site_directory);
    $this->assertFileIsNotWritable($site_directory . '/settings.php');

    parent::setUpSite();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    parent::visitInstaller();

    // Assert the title is correct and has the title suffix.
    $this->assertSession()->titleEquals('Choose language | Drupal');
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);

    $database = Database::getConnection();
    $module = $database->getProvider();
    $module_handler = \Drupal::service('module_handler');
    $module_extension_list = \Drupal::service('extension.list.module');

    // Ensure the update module is not installed.
    $this->assertFalse($module_handler->moduleExists('update'), 'The Update module is not installed.');

    // Assert that the module that is providing the database driver has been
    // installed.
    $this->assertTrue($module_handler->moduleExists($module));

    // The module that is providing the database driver should be uninstallable.
    try {
      $this->container->get('module_installer')->uninstall([$module]);
      $this->fail("Uninstalled $module module.");
    }
    catch (ModuleUninstallValidatorException $e) {
      $module_name = $module_extension_list->getName($module);
      $driver = $database->driver();
      $this->assertStringContainsString("The module '$module_name' is providing the database driver '$driver'.", $e->getMessage());
    }
  }

}
