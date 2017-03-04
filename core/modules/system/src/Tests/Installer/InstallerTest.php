<?php

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the interactive installer.
 *
 * @group Installer
 */
class InstallerTest extends InstallerTestBase {

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->rootUser->getUsername());

    // Verify that the confirmation message appears.
    require_once \Drupal::root() . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal!', [
      '@drupal' => drupal_install_profile_distribution_name(),
    ]));
  }

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage() {
    // Test that \Drupal\Core\Render\BareHtmlPageRenderer adds assets and
    // metatags as expected to the first page of the installer.
    $this->assertRaw('core/themes/seven/css/components/buttons.css');
    $this->assertRaw('<meta charset="utf-8" />');

    // Assert that the expected title is present.
    $this->assertEqual('Choose language', $this->cssSelect('main h2')[0]);

    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // Assert that the expected title is present.
    $this->assertEqual('Select an installation profile', $this->cssSelect('main h2')[0]);
    $result = $this->xpath('//span[contains(@class, :class) and contains(text(), :text)]', [':class' => 'visually-hidden', ':text' => 'Select an installation profile']);
    $this->assertEqual(count($result), 1, "Title/Label not displayed when '#title_display' => 'invisible' attribute is set");

    parent::setUpProfile();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // Assert that the expected title is present.
    $this->assertEqual('Database configuration', $this->cssSelect('main h2')[0]);

    parent::setUpSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // Assert that the expected title is present.
    $this->assertEqual('Configure site', $this->cssSelect('main h2')[0]);

    parent::setUpSite();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    parent::visitInstaller();

    // Assert the title is correct and has the title suffix.
    $this->assertTitle('Choose language | Drupal');
  }

}
