<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests for \Drupal\navigation\Form\SettingsForm.
 *
 * @group navigation
 */
class NavigationLogoTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Inject the file_system service.
    $this->fileSystem = $this->container->get('file_system');

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access navigation',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests Navigation logo configuration base options.
   */
  public function testSettingsLogoOptionsForm(): void {
    $test_files = $this->getTestFiles('image');
    // Navigate to the settings form.
    $this->drupalGet('/admin/config/user-interface/navigation/settings');
    $this->assertSession()->statusCodeEquals(200);

    // Option 1. Check the default logo provider.
    $this->assertSession()->fieldValueEquals('logo_provider', 'default');
    $this->assertSession()->elementExists('css', 'a.admin-toolbar__logo > svg');

    // Option 2: Set the logo provider to hide and check.
    $edit = [
      'logo_provider' => 'hide',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->elementNotExists('css', 'a.admin-toolbar__logo');

    // Option 3: Set the logo provider to custom and upload a logo.
    $file = reset($test_files);
    $logo_file = File::create((array) $file + ['status' => 1]);
    $logo_file->save();
    $this->assertNotEmpty($logo_file, 'File entity is not empty.');

    $edit = [
      'logo_provider' => 'custom',
      'logo_path' => $logo_file->getFileUri(),
    ];
    $this->submitForm($edit, t('Save configuration'));
    // Refresh the page to verify custom logo is placed.
    $this->drupalGet('/admin/config/user-interface/navigation/settings');
    $this->assertSession()->elementExists('css', 'a.admin-toolbar__logo > img');
    $this->assertSession()->elementAttributeContains('css', 'a.admin-toolbar__logo > img', 'src', $logo_file->getFilename());

    // Option 4: Set the custom logo to an image in the source code.
    $edit = [
      'logo_provider' => 'custom',
      'logo_path' => 'core/misc/logo/drupal-logo.svg',
    ];
    $this->submitForm($edit, t('Save configuration'));
    // Refresh the page to verify custom logo is placed.
    $this->drupalGet('/admin/config/user-interface/navigation/settings');
    $this->assertSession()->elementExists('css', 'a.admin-toolbar__logo > img');
    $this->assertSession()->elementAttributeContains('css', 'a.admin-toolbar__logo > img', 'src', 'drupal-logo.svg');

    // Option 5: Upload custom logo.
    $file = end($test_files);
    $edit = [
      'logo_provider' => 'custom',
      'files[logo_upload]' => $this->fileSystem->realpath($file->uri),
    ];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusMessageContains('The image was resized to fit within the navigation logo expected dimensions of 40x40 pixels. The new dimensions of the resized image are 40x27 pixels.');
    // Refresh the page to verify custom logo is placed.
    $this->drupalGet('/admin/config/user-interface/navigation/settings');
    $this->assertSession()->elementExists('css', 'a.admin-toolbar__logo > img');
    $this->assertSession()->elementAttributeContains('css', 'a.admin-toolbar__logo > img', 'src', $file->name);
  }

}
