<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that theme form settings works correctly.
 *
 * @group navigation
 */
class NavigationSettingsFormTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin);

    // Set expected logo dimensions smaller than core provided test images.
    \Drupal::configFactory()->getEditable('navigation.settings')
      ->set('logo_height', 10)
      ->set('logo_width', 10)
      ->save();
  }

  /**
   * Tests that submission handler works correctly.
   */
  public function testFormSettingsSubmissionHandler() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet("/admin/config/user-interface/navigation/settings");

    // Add a new managed file.
    $file = current($this->getTestFiles('image'));
    $image_file_path = \Drupal::service('file_system')->realpath($file->uri);
    $page->attachFileToField('files[logo_managed]', $image_file_path);
    $assert_session->waitForButton('logo_managed_remove_button');

    // Assert the new file is uploaded as temporary. This file should not be
    // saved as permanent if settings are not submitted.
    $image_field = $this->assertSession()->hiddenFieldExists('logo_managed[fids]');
    $file = File::load($image_field->getValue());
    $this->assertFalse($file->isPermanent());

    $page->pressButton('Save configuration');
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    $this->drupalGet("/admin/config/user-interface/navigation/settings");

    // Assert the uploaded file is saved as permanent.
    $image_field = $this->assertSession()->hiddenFieldExists('logo_managed[fids]');
    $file = File::load($image_field->getValue());
    $this->assertTrue($file->isPermanent());

    // Ensure that the image has been resized to fit in the expected container.
    $image = \Drupal::service('image.factory')->get($file->getFileUri());
    $this->assertLessThanOrEqual(10, $image->getHeight());
    $this->assertLessThanOrEqual(10, $image->getWidth());
  }

}
