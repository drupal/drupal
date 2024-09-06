<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for \Drupal\navigation\Form\SettingsForm.
 *
 * @group navigation
 */
class NavigationLogoTest extends BrowserTestBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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

    // Inject the file_system and config.factory services.
    $this->fileSystem = $this->container->get('file_system');
    $this->configFactory = $this->container->get('config.factory');

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
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->elementNotExists('css', 'a.admin-toolbar__logo');

    // Option 3: Set the logo provider to custom and upload a logo.
    $logo_file = $this->createFile();
    $this->assertNotEmpty($logo_file, 'File entity is not empty.');

    // Preset the configuration to verify a custom image is being seen.
    $config = $this->configFactory->getEditable('navigation.settings');
    $config->set('logo_provider', 'custom');
    $config->set('logo_managed', $logo_file->id());
    $config->save();
    // Refresh the page to verify custom logo is placed.
    $this->drupalGet('/admin/config/user-interface/navigation/settings');
    $this->assertSession()->elementExists('css', 'a.admin-toolbar__logo > img');
    $this->assertSession()->elementAttributeContains('css', 'a.admin-toolbar__logo > img', 'src', $logo_file->getFilename());
  }

  /**
   * Helper function to create a file entity.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFile() {
    // Define the file URI and path.
    $file_name = 'test-logo.png';
    $temp_dir = $this->fileSystem->getTempDirectory();
    $file_uri = 'public://' . $file_name;
    $logo_path = __DIR__ . '/../../assets/image_test_files/' . $file_name;
    $file_contents = file_get_contents($logo_path);
    file_put_contents($temp_dir . '/' . $file_name, $file_contents);

    // Create a file entity for testing.
    $file = File::create([
      'uri' => $file_uri,
    ]);

    try {
      $file->setPermanent();
      $file->save();
    }
    catch (EntityStorageException $e) {
      $this->fail(sprintf('Failed to create file entity: %s', $e->getMessage()));
    }

    return $file;
  }

}
