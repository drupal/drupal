<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\StandardInstallerTest.
 */

namespace Drupal\system\Tests\Installer;

/**
 * Tests the interactive installer installing the standard profile.
 *
 * @group Installer
 */
class StandardInstallerTest extends ConfigAfterInstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    // Verify that the confirmation message appears.
    require_once \Drupal::root() . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal!', array(
      '@drupal' => drupal_install_profile_distribution_name(),
    )));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // Test that the correct theme is being used.
    $this->assertNoRaw('bartik');
    $this->assertRaw('themes/seven/css/theme/install-page.css');
    parent::setUpSite();
  }

  /**
   * Ensures that the exported standard configuration is up to date.
   */
  public function testStandardConfig() {
    $skipped_config = [];
    // \Drupal\simpletest\WebTestBase::installParameters() uses
    // simpletest@example.com as mail address.
    $skipped_config['contact.form.feedback'][] = ' - simpletest@example.com';
    // \Drupal\filter\Entity\FilterFormat::toArray() drops the roles of filter
    // formats.
    $skipped_config['filter.format.basic_html'][] = 'roles:';
    $skipped_config['filter.format.basic_html'][] = ' - authenticated';
    $skipped_config['filter.format.full_html'][] = 'roles:';
    $skipped_config['filter.format.full_html'][] = ' - administrator';
    $skipped_config['filter.format.restricted_html'][] = 'roles:';
    $skipped_config['filter.format.restricted_html'][] = ' - anonymous';

    $this->assertInstalledConfig($skipped_config);
  }

}
