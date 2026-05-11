<?php

declare(strict_types=1);

namespace Drupal\Tests\responsive_image\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the responsive image field rendering.
 */
#[Group('responsive_image')]
#[RunTestsInSeparateProcesses]
class ResponsiveImageFormatterTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'breakpoint',
    'file',
    'image',
    'responsive_image',
  ];

  /**
   * The entity type.
   */
  protected string $entityType;

  /**
   * The name of the image field to use for testing.
   */
  protected string $fieldName;

  /**
   * Entity view display.
   */
  protected EntityViewDisplayInterface $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $this->entityType = 'entity_test';
    $bundle = 'entity_test';
    $this->fieldName = $this->randomMachineName();

    // Create a responsive image style.
    ResponsiveImageStyle::create([
      'id' => 'foo',
      'label' => 'Foo',
    ])->save();

    // Create an image field to be used with a responsive image formatter.
    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $bundle,
      'settings' => [
        'file_extensions' => 'jpg',
      ],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $this->display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $this->display->setComponent($this->fieldName, [
      'type' => 'responsive_image',
      'label' => 'hidden',
      'settings' => [
        'responsive_image_style' => 'foo',
        'image_link' => 'content',
      ],
    ])->save();
  }

  /**
   * Tests Image Formatter URL options handling.
   */
  public function testImageFormatterUrlOptions(): void {
    // Create a test entity with the responsive image field set.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity->{$this->fieldName}->generateSampleItems(1);
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

}
