<?php

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
    // Verify that the Standard install profile's default frontpage appears.
    $this->assertRaw('No front page content has been created yet.');
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
   * {@inheritdoc}
   */
  protected function curlExec($curl_options, $redirect = FALSE) {
    // Ensure that we see the classy progress CSS on the batch page.
    // Batch processing happens as part of HTTP redirects, so we can access the
    // HTML of the batch page.
    if (strpos($curl_options[CURLOPT_URL], '&id=1&op=do_nojs') !== FALSE) {
      $this->assertRaw('themes/classy/css/components/progress.css');
    }
    return parent::curlExec($curl_options, $redirect);
  }

  /**
   * Ensures that the exported standard configuration is up to date.
   */
  public function testStandardConfig() {
    $skipped_config = [];
    // \Drupal\simpletest\WebTestBase::installParameters() uses
    // simpletest@example.com as mail address.
    $skipped_config['contact.form.feedback'][] = '- simpletest@example.com';
    // \Drupal\filter\Entity\FilterFormat::toArray() drops the roles of filter
    // formats.
    $skipped_config['filter.format.basic_html'][] = 'roles:';
    $skipped_config['filter.format.basic_html'][] = '- authenticated';
    $skipped_config['filter.format.full_html'][] = 'roles:';
    $skipped_config['filter.format.full_html'][] = '- administrator';
    $skipped_config['filter.format.restricted_html'][] = 'roles:';
    $skipped_config['filter.format.restricted_html'][] = '- anonymous';

    $this->assertInstalledConfig($skipped_config);
  }

}
