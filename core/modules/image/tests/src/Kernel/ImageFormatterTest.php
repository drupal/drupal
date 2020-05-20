<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the image field rendering using entity fields of the image field type.
 *
 * @group image
 */
class ImageFormatterTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'image'];

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = mb_strtolower($this->randomMachineName());

    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'settings' => [
        'file_extensions' => 'jpg',
      ],
    ])->save();

    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($this->entityType, $this->bundle)
      ->setComponent($this->fieldName, [
        'type' => 'image',
        'label' => 'hidden',
      ]);
    $this->display->save();
  }

  /**
   * Tests the cache tags from image formatters.
   */
  public function testImageFormatterCacheTags() {
    // Create a test entity with the image field set.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity->{$this->fieldName}->generateSampleItems(2);
    $entity->save();

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);

    $this->assertEquals($entity->{$this->fieldName}[0]->entity->getCacheTags(), $build[$this->fieldName][0]['#cache']['tags'], 'First image cache tags is as expected');
    $this->assertEquals($entity->{$this->fieldName}[1]->entity->getCacheTags(), $build[$this->fieldName][1]['#cache']['tags'], 'Second image cache tags is as expected');
  }

  /**
   * Tests ImageFormatter's handling of SVG images.
   *
   * @requires extension gd
   */
  public function testImageFormatterSvg() {
    // Install the default image styles.
    $this->installConfig(['image']);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $png = File::create([
      'uri' => 'public://test-image.png',
    ]);
    $png->save();

    // We need to create an actual empty PNG, or the GD toolkit will not
    // consider the image valid.
    $png_resource = imagecreate(300, 300);
    imagefill($png_resource, 0, 0, imagecolorallocate($png_resource, 0, 0, 0));
    imagepng($png_resource, $png->getFileUri());

    $svg = File::create([
      'uri' => 'public://test-image.svg',
    ]);
    $svg->save();
    // We don't have to put any real SVG data in here, because the GD toolkit
    // won't be able to load it anyway.
    touch($svg->getFileUri());

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [$png, $svg],
    ]);
    $entity->save();

    // Ensure that the display is using the medium image style.
    $component = $this->display->getComponent($this->fieldName);
    $component['settings']['image_style'] = 'medium';
    $this->display->setComponent($this->fieldName, $component)->save();

    $build = $this->display->build($entity);

    // The first image is a PNG, so it is supported by the GD image toolkit.
    // The image style should be applied with its cache tags, image derivative
    // computed with its URI and dimensions.
    $this->assertCacheTags($build[$this->fieldName][0], ImageStyle::load('medium')->getCacheTags());
    $renderer->renderRoot($build[$this->fieldName][0]);
    $this->assertEquals('medium', $build[$this->fieldName][0]['#image_style']);
    // We check that the image URL contains the expected style directory
    // structure.
    $this->assertStringContainsString('styles/medium/public/test-image.png', $build[$this->fieldName][0]['#markup']);
    $this->assertStringContainsString('width="220"', $build[$this->fieldName][0]['#markup']);
    $this->assertStringContainsString('height="220"', $build[$this->fieldName][0]['#markup']);

    // The second image is an SVG, which is not supported by the GD toolkit.
    // The image style should still be applied with its cache tags, but image
    // derivative will not be available so <img> tag will point to the original
    // image.
    $this->assertCacheTags($build[$this->fieldName][1], ImageStyle::load('medium')->getCacheTags());
    $renderer->renderRoot($build[$this->fieldName][1]);
    $this->assertEquals('medium', $build[$this->fieldName][1]['#image_style']);
    // We check that the image URL does not contain the style directory
    // structure.
    $this->assertStringNotContainsString('styles/medium/public/test-image.svg', $build[$this->fieldName][1]['#markup']);
    // Since we did not store original image dimensions, width and height
    // HTML attributes will not be present.
    $this->assertStringNotContainsString('width', $build[$this->fieldName][1]['#markup']);
    $this->assertStringNotContainsString('height', $build[$this->fieldName][1]['#markup']);
  }

  /**
   * Tests Image Formatter URL options handling.
   */
  public function testImageFormatterUrlOptions() {
    $this->display->setComponent($this->fieldName, ['settings' => ['image_link' => 'content']]);

    // Create a test entity with the image field set.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity->{$this->fieldName}->generateSampleItems(2);
    $entity->save();

    // Generate the render array to verify URL options are as expected.
    $build = $this->display->build($entity);
    $this->assertInstanceOf(Url::class, $build[$this->fieldName][0]['#url']);
    $build[$this->fieldName][0]['#url']->setOption('attributes', ['data-attributes-test' => 'test123']);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $output = $renderer->renderRoot($build[$this->fieldName][0]);
    $this->assertStringContainsString('<a href="' . $entity->toUrl()->toString() . '" data-attributes-test="test123"', (string) $output);
  }

  /**
   * Asserts that a renderable array has a set of cache tags.
   *
   * @param array $renderable
   *   The renderable array. Must have a #cache[tags] element.
   * @param array $cache_tags
   *   The expected cache tags.
   */
  protected function assertCacheTags(array $renderable, array $cache_tags) {
    $diff = array_diff($cache_tags, $renderable['#cache']['tags']);
    $this->assertEmpty($diff);
  }

}
