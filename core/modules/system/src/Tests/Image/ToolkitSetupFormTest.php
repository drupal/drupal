<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Image\ToolkitSetupFormTest.
 */

namespace Drupal\system\Tests\Image;

use Drupal\simpletest\WebTestBase;

/**
 * Tests image toolkit setup form.
 *
 * @group Image
 */
class ToolkitSetupFormTest extends WebTestBase {

  /**
   * Admin user account.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'image_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
    ));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test Image toolkit setup form.
   */
  function testToolkitSetupForm() {
    // Get form.
    $this->drupalGet('admin/config/media/image-toolkit');

    // Test that default toolkit is GD.
    $this->assertFieldByName('image_toolkit', 'gd', 'The default image toolkit is GD.');

    // Test changing the jpeg image quality.
    $edit = array('gd[image_jpeg_quality]' => '70');
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertEqual(\Drupal::config('system.image.gd')->get('jpeg_quality'), '70');

    // Test changing the toolkit.
    $edit = array('image_toolkit' => 'test');
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertEqual(\Drupal::config('system.image')->get('toolkit'), 'test');
    $this->assertFieldByName('test[test_parameter]', '10');

    // Test changing the test toolkit parameter.
    $edit = array('test[test_parameter]' => '0');
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertText(t('Test parameter should be different from 0.'), 'Validation error displayed.');
    $edit = array('test[test_parameter]' => '20');
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertEqual(\Drupal::config('system.image.test_toolkit')->get('test_parameter'), '20');

    // Test access without the permission 'administer site configuration'.
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages')));
    $this->drupalGet('admin/config/media/image-toolkit');
    $this->assertResponse(403);
  }
}
