<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageThemeFunctionTest.
 */

namespace Drupal\image\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests image theme functions.
 *
 * @group image
 */
class ImageThemeFunctionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image', 'entity_test');

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $image;

  /**
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  protected function setUp() {
    parent::setUp();

    entity_create('field_storage_config', array(
      'name' => 'image_test',
      'entity_type' => 'entity_test',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'bundle' => 'entity_test',
    ))->save();
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = entity_create('file', array(
      'uri' => 'public://example.jpg',
    ));
    $this->image->save();
    $this->imageFactory = $this->container->get('image.factory');
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
    $style = entity_create('image_style', array('name' => 'test', 'label' => 'Test'));
    $style->save();
    $url = $style->buildUrl($original_uri);

    // Create a test entity with the image field set.
    $entity = entity_create('entity_test');
    $entity->image_test->target_id = $this->image->id();
    $entity->image_test->alt = NULL;
    $entity->image_test->uri = $original_uri;
    $image = $this->imageFactory->get('public://example.jpg');
    $entity->save();

    // Create the base element that we'll use in the tests below.
    $path = $this->randomMachineName();
    $base_element = array(
      '#theme' => 'image_formatter',
      '#image_style' => 'test',
      '#item' => $entity->image_test,
      '#path' => array(
        'path' => $path,
      ),
    );

    // Test using theme_image_formatter() with a NULL value for the alt option.
    $element = $base_element;
    $this->drupalSetContent(drupal_render($element));
    $elements = $this->xpath('//a[@href=:path]/img[@class="image-style-test" and @src=:url and @width=:width and @height=:height]', array(':path' => base_path() . $path, ':url' => $url, ':width' => $image->getWidth(), ':height' => $image->getHeight()));
    $this->assertEqual(count($elements), 1, 'theme_image_formatter() correctly renders with a NULL value for the alt option.');

    // Test using theme_image_formatter() without an image title, alt text, or
    // link options.
    $element = $base_element;
    $element['#item']->alt = '';
    $this->drupalSetContent(drupal_render($element));
    $elements = $this->xpath('//a[@href=:path]/img[@class="image-style-test" and @src=:url and @width=:width and @height=:height and @alt=""]', array(':path' => base_path() . $path, ':url' => $url, ':width' => $image->getWidth(), ':height' => $image->getHeight()));
    $this->assertEqual(count($elements), 1, 'theme_image_formatter() correctly renders without title, alt, or path options.');

    // Link the image to a fragment on the page, and not a full URL.
    $fragment = $this->randomMachineName();
    $element = $base_element;
    $element['#path']['path'] = '';
    $element['#path']['options'] = array(
      'external' => TRUE,
      'fragment' => $fragment,
    );
    $this->drupalSetContent(drupal_render($element));
    $elements = $this->xpath('//a[@href=:fragment]/img[@class="image-style-test" and @src=:url and @width=:width and @height=:height and @alt=""]', array(':fragment' => '#' . $fragment, ':url' => $url, ':width' => $image->getWidth(), ':height' => $image->getHeight()));
    $this->assertEqual(count($elements), 1, 'theme_image_formatter() correctly renders a link fragment.');
  }

  /**
   * Tests usage of the image style theme function.
   */
  function testImageStyleTheme() {
    // Create an image.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = file_unmanaged_copy($file->uri, 'public://', FILE_EXISTS_RENAME);

    // Create a style.
    $style = entity_create('image_style', array('name' => 'image_test', 'label' => 'Test'));
    $style->save();
    $url = $style->buildUrl($original_uri);

    // Create the base element that we'll use in the tests below.
    $base_element = array(
      '#theme' => 'image_style',
      '#style_name' => 'image_test',
      '#uri' => $original_uri,
    );

    $element = $base_element;
    $this->drupalSetContent(drupal_render($element));
    $elements = $this->xpath('//img[@class="image-style-image-test" and @src=:url and @alt=""]', array(':url' => $url));
    $this->assertEqual(count($elements), 1, 'theme_image_style() renders an image correctly.');

    // Test using theme_image_style() with a NULL value for the alt option.
    $element = $base_element;
    $element['#alt'] = NULL;
    $this->drupalSetContent(drupal_render($element));
    $elements = $this->xpath('//img[@class="image-style-image-test" and @src=:url]', array(':url' => $url));
    $this->assertEqual(count($elements), 1, 'theme_image_style() renders an image correctly with a NULL value for the alt option.');
  }

  /**
   * Tests image alt attribute functionality.
   */
  function testImageAltFunctionality() {
    // Test using alt directly with alt attribute.
    $image_with_alt_property = array(
      '#theme' => 'image',
      '#uri' => '/core/themes/bartik/logo.png',
      '#alt' => 'Regular alt',
      '#title' => 'Test title',
      '#width' => '50%',
      '#height' => '50%',
      '#attributes' => array('class' => 'image-with-regular-alt', 'id' => 'my-img'),
    );

    $this->drupalSetContent(drupal_render($image_with_alt_property));
    $elements = $this->xpath('//img[contains(@class, class) and contains(@alt, :alt)]', array(":class" => "image-with-regular-alt", ":alt" => "Regular alt"));
    $this->assertEqual(count($elements), 1, 'Regular alt displays correctly');

    // Test using alt attribute inside attributes.
    $image_with_alt_attribute_alt_attribute = array(
      '#theme' => 'image',
      '#uri' => '/core/themes/bartik/logo.png',
      '#width' => '50%',
      '#height' => '50%',
      '#attributes' => array(
        'class' => 'image-with-attribute-alt',
        'id' => 'my-img',
        'title' => 'New test title',
        'alt' => 'Attribute alt',
      ),
    );

    $this->drupalSetContent(drupal_render($image_with_alt_attribute_alt_attribute));
    $elements = $this->xpath('//img[contains(@class, class) and contains(@alt, :alt)]', array(":class" => "image-with-attribute-alt", ":alt" => "Attribute alt"));
    $this->assertEqual(count($elements), 1, 'Attribute alt displays correctly');

    // Test using alt attribute as property and inside attributes.
    $image_with_alt_attribute_both = array(
      '#theme' => 'image',
      '#uri' => '/core/themes/bartik/logo.png',
      '#width' => '50%',
      '#height' => '50%',
      '#alt' => 'Kitten sustainable',
      '#attributes' => array(
        'class' => 'image-with-attribute-alt',
        'id' => 'my-img',
        'title' => 'New test title',
        'alt' => 'Attribute alt',
      ),
    );

    $this->drupalSetContent(drupal_render($image_with_alt_attribute_both));
    $elements = $this->xpath('//img[contains(@class, class) and contains(@alt, :alt)]', array(":class" => "image-with-attribute-alt", ":alt" => "Attribute alt"));
    $this->assertEqual(count($elements), 1, 'Attribute alt overrides alt property if both set.');
  }
}
