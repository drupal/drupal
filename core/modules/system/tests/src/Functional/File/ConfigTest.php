<?php

namespace Drupal\Tests\system\Functional\File;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests file system configuration operations.
 *
 * @group File
 */
class ConfigTest extends BrowserTestBase {

  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));
  }

  /**
   * Tests file configuration page.
   */
  public function testFileConfigurationPage() {
    $this->drupalGet('admin/config/media/file-system');

    // Set the file paths to non-default values.
    // The respective directories are created automatically
    // upon form submission.
    $file_path = $this->publicFilesDirectory;
    $fields = [
      'file_temporary_path' => $file_path . '/file_config_page_test/temporary',
      'file_default_scheme' => 'private',
    ];

    // Check that public and private can be selected as default scheme.
    $this->assertText('Public local files served by the webserver.');
    $this->assertText('Private local files served by Drupal.');

    $this->drupalPostForm(NULL, $fields, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    foreach ($fields as $field => $value) {
      $this->assertFieldByName($field, $value);
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
