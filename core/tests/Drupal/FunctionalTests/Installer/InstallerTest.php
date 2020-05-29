<?php

namespace Drupal\FunctionalTests\Installer;

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
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->rootUser->getAccountName());

    // Verify that the confirmation message appears.
    require_once $this->root . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal!', [
      '@drupal' => drupal_install_profile_distribution_name(),
    ]));

    // Ensure that the timezone is correct for sites under test after installing
    // interactively.
    $this->assertEqual($this->config('system.date')->get('timezone.default'), 'Australia/Sydney');

    // Ensure the profile has a weight of 1000.
    $module_extension_list = \Drupal::service('extension.list.module');
    $extensions = $module_extension_list->getList();

    $this->assertArrayHasKey('testing', $extensions);
    $this->assertEquals(1000, $extensions['testing']->weight);
  }

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage() {
    // Test that \Drupal\Core\Render\BareHtmlPageRenderer adds assets and
    // metatags as expected to the first page of the installer.
    $this->assertRaw("core/themes/seven/css/components/buttons.css");
    $this->assertRaw('<meta charset="utf-8" />');

    // Assert that the expected title is present.
    $this->assertEqual('Choose language', $this->cssSelect('main h2')[0]->getText());

    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // Assert that the expected title is present.
    $this->assertEqual('Select an installation profile', $this->cssSelect('main h2')[0]->getText());
    $result = $this->xpath('//span[contains(@class, :class) and contains(text(), :text)]', [':class' => 'visually-hidden', ':text' => 'Select an installation profile']);
    $this->assertCount(1, $result, "Title/Label not displayed when '#title_display' => 'invisible' attribute is set");

    parent::setUpProfile();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // Assert that the expected title is present.
    $this->assertEqual('Database configuration', $this->cssSelect('main h2')[0]->getText());

    // Assert that we use the by core supported database drivers by default and
    // not the ones from the driver_test module.
    $elements = $this->xpath('//label[@for="edit-driver-mysql"]');
    $this->assertEqual(current($elements)->getText(), 'MySQL, MariaDB, Percona Server, or equivalent');
    $elements = $this->xpath('//label[@for="edit-driver-pgsql"]');
    $this->assertEqual(current($elements)->getText(), 'PostgreSQL');

    parent::setUpSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // Assert that the expected title is present.
    $this->assertEqual('Configure site', $this->cssSelect('main h2')[0]->getText());

    // Test that SiteConfigureForm::buildForm() has made the site directory and
    // the settings file non-writable.
    $site_directory = $this->container->getParameter('app.root') . '/' . $this->siteDirectory;
    $this->assertDirectoryNotIsWritable($site_directory);
    $this->assertFileNotIsWritable($site_directory . '/settings.php');

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

}
