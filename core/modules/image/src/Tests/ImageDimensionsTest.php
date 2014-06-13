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
    $image_factory = $this->container->get('image.factory');
    // Create a working copy of the file.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = file_unmanaged_copy($file->uri, 'public://', FILE_EXISTS_RENAME);

    // Create a style.
    /** @var $style \Drupal\image\ImageStyleInterface */
    $style = entity_create('image_style', array('name' => 'test', 'label' => 'Test'));
    $style->save();
    $generated_uri = 'public://styles/test/public/'. drupal_basename($original_uri);
    $url = $style->buildUrl($original_uri);

    $variables = array(
      '#theme' => 'image_style',
      '#style_name' => 'test',
      '#uri' => $original_uri,
      '#width' => 40,
      '#height' => 20,
    );
    // Verify that the original image matches the hard-coded values.
    $image_file = $image_factory->get($original_uri);
    $this->assertEqual($image_file->getWidth(), $variables['#width']);
    $this->assertEqual($image_file->getHeight(), $variables['#height']);

    // Scale an image that is wider than it is high.
    $effect = array(
      'id' => 'image_scale',
      'data' => array(
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ),
      'weight' => 0,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" width="120" height="60" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual($image_file->getWidth(), 120);
    $this->assertEqual($image_file->getHeight(), 60);

    // Rotate 90 degrees anticlockwise.
    $effect = array(
      'id' => 'image_rotate',
      'data' => array(
        'degrees' => -90,
        'random' => FALSE,
      ),
      'weight' => 1,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" width="60" height="120" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual($image_file->getWidth(), 60);
    $this->assertEqual($image_file->getHeight(), 120);

    // Scale an image that is higher than it is wide (rotated by previous effect).
    $effect = array(
      'id' => 'image_scale',
      'data' => array(
        'width' => 120,
        'height' => 90,
        'upscale' => TRUE,
      ),
      'weight' => 2,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" width="45" height="90" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual($image_file->getWidth(), 45);
    $this->assertEqual($image_file->getHeight(), 90);

    // Test upscale disabled.
    $effect = array(
      'id' => 'image_scale',
      'data' => array(
        'width' => 400,
        'height' => 200,
        'upscale' => FALSE,
      ),
      'weight' => 3,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" width="45" height="90" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual($image_file->getWidth(), 45);
    $this->assertEqual($image_file->getHeight(), 90);

    // Add a desaturate effect.
    $effect = array(
      'id' => 'image_desaturate',
      'data' => array(),
      'weight' => 4,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" width="45" height="90" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual($image_file->getWidth(), 45);
    $this->assertEqual($image_file->getHeight(), 90);

    // Add a random rotate effect.
    $effect = array(
      'id' => 'image_rotate',
      'data' => array(
        'degrees' => 180,
        'random' => TRUE,
      ),
      'weight' => 5,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');


    // Add a crop effect.
    $effect = array(
      'id' => 'image_crop',
      'data' => array(
        'width' => 30,
        'height' => 30,
        'anchor' => 'center-center',
      ),
      'weight' => 6,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" width="30" height="30" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');
    $image_file = $image_factory->get($generated_uri);
    $this->assertEqual($image_file->getWidth(), 30);
    $this->assertEqual($image_file->getHeight(), 30);

    // Rotate to a non-multiple of 90 degrees.
    $effect = array(
      'id' => 'image_rotate',
      'data' => array(
        'degrees' => 57,
        'random' => FALSE,
      ),
      'weight' => 7,
    );

    $effect_id = $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" alt="" />');
    $this->assertFalse(file_exists($generated_uri), 'Generated file does not exist.');
    $this->drupalGet($url);
    $this->assertResponse(200, 'Image was generated at the URL.');
    $this->assertTrue(file_exists($generated_uri), 'Generated file does exist after we accessed it.');

    $effect_plugin = $style->getEffect($effect_id);
    $style->deleteImageEffect($effect_plugin);

    // Ensure that an effect with no dimensions callback unsets the dimensions.
    // This ensures compatibility with 7.0 contrib modules.
    $effect = array(
      'id' => 'image_module_test_null',
      'data' => array(),
      'weight' => 8,
    );

    $style->addImageEffect($effect);
    $style->save();
    $this->assertEqual($this->getImageTag($variables), '<img class="image-style-test" src="' . $url . '" alt="" />');
  }

  /**
   * Render an image style element.
   *
   * drupal_render() alters the passed $variables array by adding a new key
   * '#printed' => TRUE. This prevents next call to re-render the element. We
   * wrap drupal_render() in a helper protected method and pass each time a
   * fresh array so that $variables won't get altered and the element is
   * re-rendered each time.
   */
  protected function getImageTag($variables) {
    return str_replace("\n", NULL, drupal_render($variables));
  }

}
