<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageDimensionsTest.
 */

namespace Drupal\image\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that images have correct dimensions when styled.
 */
class ImageDimensionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image', 'image_module_test');

  protected $profile = 'testing';

  public static function getInfo() {
    return array(
      'name' => 'Image dimensions',
      'description' => 'Tests that images have correct dimensions when styled.',
      'group' => 'Image',
    );
  }

  /**
   * Test styled image dimensions cumulatively.
   */
  function testImageDimensions() {
    // Create a working copy of the file.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = file_unmanaged_copy($file->uri, 'public://', FILE_EXISTS_RENAME);

    // Create a style.
    $style = entity_create('image_style', array('name' => 'test', 'label' => 'Test'));
    $style->save();
    $generated_uri = 'public://styles/test/public/'. drupal_basename($original_uri);
    $url = $style->buildUrl($original_uri);

    $variables = array(
      'style_name' => 'test',
      'uri' => $original_uri,
      'width' => 40,
      'height' => 20,
    );
    // Verify that the original image matches the hard-coded values.
    $image_info = image_get_info($original_uri);
    $this->assertEqual($image_info['width'], $variables['width']);
    $this->assertEqual($image_info['height'], $variables['height']);

    // Scale an image that is wider than it is high.
    $effect = array(
      'name' => 'image_scale',
      'data' => array(
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ),
      'weight' => 0,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" width="120" height="60" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_info = image_get_info($generated_uri);
    $this->assertEqual($image_info['width'], 120);
    $this->assertEqual($image_info['height'], 60);

    // Rotate 90 degrees anticlockwise.
    $effect = array(
      'name' => 'image_rotate',
      'data' => array(
        'degrees' => -90,
        'random' => FALSE,
      ),
      'weight' => 1,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" width="60" height="120" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_info = image_get_info($generated_uri);
    $this->assertEqual($image_info['width'], 60);
    $this->assertEqual($image_info['height'], 120);

    // Scale an image that is higher than it is wide (rotated by previous effect).
    $effect = array(
      'name' => 'image_scale',
      'data' => array(
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ),
      'weight' => 2,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" width="45" height="90" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_info = image_get_info($generated_uri);
    $this->assertEqual($image_info['width'], 45);
    $this->assertEqual($image_info['height'], 90);

    // Test upscale disabled.
    $effect = array(
      'name' => 'image_scale',
      'data' => array(
        'width' => 400,
        'height' => 200,
        'upscale' => FALSE,
      ),
      'weight' => 3,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" width="45" height="90" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_info = image_get_info($generated_uri);
    $this->assertEqual($image_info['width'], 45);
    $this->assertEqual($image_info['height'], 90);

    // Add a desaturate effect.
    $effect = array(
      'name' => 'image_desaturate',
      'data' => array(),
      'weight' => 4,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" width="45" height="90" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_info = image_get_info($generated_uri);
    $this->assertEqual($image_info['width'], 45);
    $this->assertEqual($image_info['height'], 90);

    // Add a random rotate effect.
    $effect = array(
      'name' => 'image_rotate',
      'data' => array(
        'degrees' => 180,
        'random' => TRUE,
      ),
      'weight' => 5,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');


    // Add a crop effect.
    $effect = array(
      'name' => 'image_crop',
      'data' => array(
        'width' => 30,
        'height' => 30,
        'anchor' => 'center-center',
      ),
      'weight' => 6,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" width="30" height="30" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_info = image_get_info($generated_uri);
    $this->assertEqual($image_info['width'], 30);
    $this->assertEqual($image_info['height'], 30);

    // Rotate to a non-multiple of 90 degrees.
    $effect = array(
      'name' => 'image_rotate',
      'data' => array(
        'degrees' => 57,
        'random' => FALSE,
      ),
      'weight' => 7,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');

    image_effect_delete($style, $effect);

    // Ensure that an effect with no dimensions callback unsets the dimensions.
    // This ensures compatibility with 7.0 contrib modules.
    $effect = array(
      'name' => 'image_module_test_null',
      'data' => array(),
      'weight' => 8,
    );

    image_effect_save($style, $effect);
    $img_tag = theme_image_style($variables);
    $this->assertEqual($img_tag, '<img class="image-style-test" src="' . $url . '" alt="" />');
  }
}
