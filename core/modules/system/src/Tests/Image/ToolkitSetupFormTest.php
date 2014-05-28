<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Image\ToolkitSetupFormTest.
 */

namespace Drupal\system\Tests\Image;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the Image toolkit setup form.
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
  public static function getInfo() {
    return array(
      'name' => 'Image toolkit setup form tests',
      'description' => 'Check image toolkit setup form.',
      'group' => 'Image',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array(
      'access administration pages',
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
    $edit = array('test[test_parameter]' => '20');
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertEqual(\Drupal::config('system.image.test_toolkit')->get('test_parameter'), '20');
  }
}
