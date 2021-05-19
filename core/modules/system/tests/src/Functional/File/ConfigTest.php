<?php

namespace Drupal\Tests\system\Functional\File;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests file system configuration operations.
 *
 * @group File
 */
class ConfigTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));
  }

  /**
   * Tests file configuration page.
   */
  public function testFileConfigurationPage() {
    $this->drupalGet('admin/config/media/file-system');

    // Set the file paths to non-default values.
    // The respective directories are created automatically
    // upon form submission.
    $fields = [
      'file_default_scheme' => 'private',
    ];

    // Check that public and private can be selected as default scheme.
    $this->assertText('Public local files served by the webserver.');
    $this->assertText('Private local files served by Drupal.');

    $this->submitForm($fields, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
    foreach ($fields as $field => $value) {
      $this->assertSession()->fieldValueEquals($field, $value);
    }

    // Remove the private path, rebuild the container and verify that private
    // can no longer be selected in the UI.
    $settings['settings']['file_private_path'] = (object) [
      'value' => '',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    $this->rebuildContainer();

    $this->drupalGet('admin/config/media/file-system');
    $this->assertText('Public local files served by the webserver.');
    $this->assertNoText('Private local files served by Drupal.');
  }

}
