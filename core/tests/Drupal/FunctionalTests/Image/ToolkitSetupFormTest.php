<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Image;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests image toolkit setup form.
 *
 * @group Image
 */
class ToolkitSetupFormTest extends BrowserTestBase {

  /**
   * Admin user account.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'image', 'image_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests Image toolkit setup form.
   */
  public function testToolkitSetupForm(): void {
    // Get form.
    $this->drupalGet('admin/config/media/image-toolkit');

    // Test that default toolkit is GD.
    $this->assertSession()->fieldValueEquals('image_toolkit', 'gd');

    // Test changing the jpeg image quality.
    $edit = ['gd[image_jpeg_quality]' => '70'];
    $this->submitForm($edit, 'Save configuration');
    $this->assertEquals('70', $this->config('system.image.gd')->get('jpeg_quality'));

    // Test changing the toolkit.
    $edit = ['image_toolkit' => 'test'];
    $this->submitForm($edit, 'Save configuration');
    $this->assertEquals('test', $this->config('system.image')->get('toolkit'));
    $this->assertSession()->fieldValueEquals('test[test_parameter]', '10');

    // Test changing the test toolkit parameter.
    $edit = ['test[test_parameter]' => '0'];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('Test parameter should be different from 0.');
    $edit = ['test[test_parameter]' => '20'];
    $this->submitForm($edit, 'Save configuration');
    $this->assertEquals('20', $this->config('system.image.test_toolkit')->get('test_parameter'));

    // Test access without the permission 'administer site configuration'.
    $this->drupalLogin($this->drupalCreateUser(['access administration pages']));
    $this->drupalGet('admin/config/media/image-toolkit');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests GD toolkit requirements on the Status Report.
   */
  public function testGdToolkitRequirements(): void {
    // Get Status Report.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('GD2 image manipulation toolkit');
    $this->assertSession()->pageTextContains('Supported image file formats: GIF, JPEG, PNG, WEBP, AVIF.');
  }

}
