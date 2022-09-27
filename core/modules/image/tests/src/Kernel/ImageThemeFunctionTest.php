<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests image theme functions.
 *
 * @group image
 */
class ImageThemeFunctionTest extends KernelTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'field',
    'file',
    'image',
    'system',
    'user',
  ];

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'bundle' => 'entity_test',
    ])->save();
    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = File::create([
      'uri' => 'public://example.jpg',
    ]);
    $this->image->save();
    $this->imageFactory = $this->container->get('image.factory');
  }

  /**
   * Tests usage of the image field formatters.
   */
  public function testImageFormatterTheme() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create an image.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = \Drupal::service('file_system')->copy($file->uri, 'public://', FileSystemInterface::EXISTS_RENAME);

    // Create a style.
    $style = ImageStyle::create(['name' => 'test', 'label' => 'Test']);
    $style->save();
    $url = \Drupal::service('file_url_generator')->transformRelative($style->buildUrl($original_uri));

    // Create a test entity with the image field set.
    $entity = EntityTest::create();
    $entity->image_test->target_id = $this->image->id();
    $entity->image_test->alt = NULL;
    $entity->image_test->uri = $original_uri;
    $image = $this->imageFactory->get('public://example.jpg');
    $entity->save();

    // Create the base element that we'll use in the tests below.
    $path = $this->randomMachineName();
    $base_element = [
      '#theme' => 'image_formatter',
      '#image_style' => 'test',
      '#item' => $entity->image_test,
      '#url' => Url::fromUri('base:' . $path),
    ];

    // Test using theme_image_formatter() with a NULL value for the alt option.
    $element = $base_element;
    $this->setRawContent($renderer->renderRoot($element));
    $elements = $this->xpath('//a[@href=:path]/img[@src=:url and @width=:width and @height=:height]', [':path' => base_path() . $path, ':url' => $url, ':width' => $image->getWidth(), ':height' => $image->getHeight()]);
    $this->assertCount(1, $elements, 'theme_image_formatter() correctly renders with a NULL value for the alt option.');

    // Test using theme_image_formatter() without an image title, alt text, or
    // link options.
    $element = $base_element;
    $element['#item']->alt = '';
    $this->setRawContent($renderer->renderRoot($element));
    $elements = $this->xpath('//a[@href=:path]/img[@src=:url and @width=:width and @height=:height and @alt=""]', [':path' => base_path() . $path, ':url' => $url, ':width' => $image->getWidth(), ':height' => $image->getHeight()]);
    $this->assertCount(1, $elements, 'theme_image_formatter() correctly renders without title, alt, or path options.');

    // Link the image to a fragment on the page, and not a full URL.
    $fragment = $this->randomMachineName();
    $element = $base_element;
    $element['#url'] = Url::fromRoute('<none>', [], ['fragment' => $fragment]);
    $this->setRawContent($renderer->renderRoot($element));
    $elements = $this->xpath('//a[@href=:fragment]/img[@src=:url and @width=:width and @height=:height and @alt=""]', [
      ':fragment' => '#' . $fragment,
      ':url' => $url,
      ':width' => $image->getWidth(),
      ':height' => $image->getHeight(),
    ]);
    $this->assertCount(1, $elements, 'theme_image_formatter() correctly renders a link fragment.');
  }

  /**
   * Tests usage of the image style theme function.
   */
  public function testImageStyleTheme() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create an image.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = \Drupal::service('file_system')->copy($file->uri, 'public://', FileSystemInterface::EXISTS_RENAME);

    // Create a style.
    $style = ImageStyle::create(['name' => 'image_test', 'label' => 'Test']);
    $style->save();
    $url = \Drupal::service('file_url_generator')->transformRelative($style->buildUrl($original_uri));

    // Create the base element that we'll use in the tests below.
    $base_element = [
      '#theme' => 'image_style',
      '#style_name' => 'image_test',
      '#uri' => $original_uri,
    ];

    $element = $base_element;
    $this->setRawContent($renderer->renderRoot($element));
    $elements = $this->xpath('//img[@src=:url and @alt=""]', [':url' => $url]);
    $this->assertCount(1, $elements, 'theme_image_style() renders an image correctly.');

    // Test using theme_image_style() with a NULL value for the alt option.
    $element = $base_element;
    $element['#alt'] = NULL;
    $this->setRawContent($renderer->renderRoot($element));
    $elements = $this->xpath('//img[@src=:url]', [':url' => $url]);
    $this->assertCount(1, $elements, 'theme_image_style() renders an image correctly with a NULL value for the alt option.');
  }

  /**
   * Tests image alt attribute functionality.
   */
  public function testImageAltFunctionality() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Test using alt directly with alt attribute.
    $image_with_alt_property = [
      '#theme' => 'image',
      '#uri' => '/core/themes/olivero/logo.svg',
      '#alt' => 'Regular alt',
      '#title' => 'Test title',
      '#width' => '50%',
      '#height' => '50%',
      '#attributes' => ['class' => 'image-with-regular-alt', 'id' => 'my-img'],
    ];

    $this->setRawContent($renderer->renderRoot($image_with_alt_property));
    $elements = $this->xpath('//img[contains(@class, class) and contains(@alt, :alt)]', [":class" => "image-with-regular-alt", ":alt" => "Regular alt"]);
    $this->assertCount(1, $elements, 'Regular alt displays correctly');

    // Test using alt attribute inside attributes.
    $image_with_alt_attribute_alt_attribute = [
      '#theme' => 'image',
      '#uri' => '/core/themes/olivero/logo.svg',
      '#width' => '50%',
      '#height' => '50%',
      '#attributes' => [
        'class' => 'image-with-attribute-alt',
        'id' => 'my-img',
        'title' => 'New test title',
        'alt' => 'Attribute alt',
      ],
    ];

    $this->setRawContent($renderer->renderRoot($image_with_alt_attribute_alt_attribute));
    $elements = $this->xpath('//img[contains(@class, class) and contains(@alt, :alt)]', [":class" => "image-with-attribute-alt", ":alt" => "Attribute alt"]);
    $this->assertCount(1, $elements, 'Attribute alt displays correctly');

    // Test using alt attribute as property and inside attributes.
    $image_with_alt_attribute_both = [
      '#theme' => 'image',
      '#uri' => '/core/themes/olivero/logo.svg',
      '#width' => '50%',
      '#height' => '50%',
      '#alt' => 'Kitten sustainable',
      '#attributes' => [
        'class' => 'image-with-attribute-alt',
        'id' => 'my-img',
        'title' => 'New test title',
        'alt' => 'Attribute alt',
      ],
    ];

    $this->setRawContent($renderer->renderRoot($image_with_alt_attribute_both));
    $elements = $this->xpath('//img[contains(@class, class) and contains(@alt, :alt)]', [":class" => "image-with-attribute-alt", ":alt" => "Attribute alt"]);
    $this->assertCount(1, $elements, 'Attribute alt overrides alt property if both set.');
  }

}
