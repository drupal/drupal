<?php

namespace Drupal\FunctionalTests\Installer;

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
    // Verify that the Standard install profile's default frontpage appears.
    $this->assertRaw('No front page content has been created yet.');
    // Ensure that the contact link enabled in standard_install() works as
    // expected.
    $this->clickLink('Contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('contact');
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
    // FunctionalTestSetupTrait::installParameters() uses Drupal as site name
    // and simpletest@example.com as mail address.
    $skipped_config['system.site'][] = 'name: Drupal';
    $skipped_config['system.site'][] = 'mail: simpletest@example.com';
    $skipped_config['contact.form.feedback'][] = '- simpletest@example.com';
    // \Drupal\filter\Entity\FilterFormat::toArray() drops the roles of filter
    // formats.
    $skipped_config['filter.format.basic_html'][] = 'roles:';
    $skipped_config['filter.format.basic_html'][] = '- authenticated';
    $skipped_config['filter.format.full_html'][] = 'roles:';
    $skipped_config['filter.format.full_html'][] = '- administrator';
    $skipped_config['filter.format.restricted_html'][] = 'roles:';
    $skipped_config['filter.format.restricted_html'][] = '- anonymous';
    // The site UUID is set dynamically for each installation.
    $skipped_config['system.site'][] = 'uuid: ' . $this->config('system.site')->get('uuid');

    $this->assertInstalledConfig($skipped_config);
  }

}
