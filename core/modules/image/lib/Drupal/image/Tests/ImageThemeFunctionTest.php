<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageThemeFunctionTest.
 */

namespace Drupal\image\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests image theme functions.
 */
class ImageThemeFunctionTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Image theme functions',
      'description' => 'Tests the image theme functions.',
      'group' => 'Image',
    );
  }

  function setUp() {
    parent::setUp(array('image'));
  }

  /**
   * Tests usage of the image field formatters.
   */
  function testImageFormatterTheme() {
    // Create an image.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = file_unmanaged_copy($file->uri, 'public://', FILE_EXISTS_RENAME);

    // Create a style.
    image_style_save(array('name' => 'test'));
    $url = image_style_url('test', $original_uri);

    // Test using theme_image_formatter() without an image title, alt text, or
    // link options.
    $path = $this->randomName();
    $element = array(
      '#theme' => 'image_formatter',
      '#image_style' => 'test',
      '#item' => array(
        'uri' => $original_uri,
      ),
      '#path' => array(
        'path' => $path,
      ),
    );
    $rendered_element = render($element);
    $expected_result = '<a href="' . base_path() . $path . '"><img src="' . $url . '" alt="" /></a>';
    $this->assertEqual($expected_result, $rendered_element, 'theme_image_formatter() correctly renders without title, alt, or path options.');

    // Link the image to a fragment on the page, and not a full URL.
    $fragment = $this->randomName();
    $element['#path']['path'] = '';
    $element['#path']['options'] = array(
      'external' => TRUE,
      'fragment' => $fragment,
    );
    $rendered_element = render($element);
    $expected_result = '<a href="#' . $fragment . '"><img src="' . $url . '" alt="" /></a>';
    $this->assertEqual($expected_result, $rendered_element, 'theme_image_formatter() correctly renders a link fragment.');
  }

}
