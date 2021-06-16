<?php

namespace Drupal\Tests\responsive_image\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\responsive_image\Entity\ResponsiveImageStyle
 * @group block
 */
class ResponsiveImageStyleConfigEntityUnitTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The breakpoint manager used for testing.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $breakpointManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('responsive_image'));

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('responsive_image_style')
      ->will($this->returnValue($this->entityType));

    $this->breakpointManager = $this->createMock('\Drupal\breakpoint\BreakpointManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('breakpoint.manager', $this->breakpointManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Set up image style loading mock.
    $styles = [];
    foreach (['fallback', 'small', 'medium', 'large'] as $style) {
      $mock = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
      $mock->expects($this->any())
        ->method('getConfigDependencyName')
        ->willReturn('image.style.' . $style);
      $styles[$style] = $mock;
    }
    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->with(array_keys($styles))
      ->willReturn($styles);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('image_style')
      ->willReturn($storage);

    $entity_type_repository = $this->getMockForAbstractClass(EntityTypeRepositoryInterface::class);
    $entity_type_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->with('Drupal\image\Entity\ImageStyle')
      ->willReturn('image_style');

    $entity = new ResponsiveImageStyle(['breakpoint_group' => 'test_group']);
    $entity->setBreakpointGroup('test_group');
    $entity->setFallbackImageStyle('fallback');
    $entity->addImageStyleMapping('test_breakpoint', '1x', ['image_mapping_type' => 'image_style', 'image_mapping' => 'small']);
    $entity->addImageStyleMapping('test_breakpoint', '2x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => [
          'medium' => 'medium',
          'large' => 'large',
        ],
      ],
    ]);

    $this->breakpointManager->expects($this->any())
      ->method('getGroupProviders')
      ->with('test_group')
      ->willReturn(['bartik' => 'theme', 'toolbar' => 'module']);

    \Drupal::getContainer()->set('entity_type.repository', $entity_type_repository);

    $dependencies = $entity->calculateDependencies()->getDependencies();
    $this->assertEquals(['toolbar'], $dependencies['module']);
    $this->assertEquals(['bartik'], $dependencies['theme']);
    $this->assertEquals(['image.style.fallback', 'image.style.large', 'image.style.medium', 'image.style.small'], $dependencies['config']);
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::hasImageStyleMappings
   */
  public function testHasImageStyleMappings() {
    $entity = new ResponsiveImageStyle([]);
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => '',
    ]);
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '(min-width:700px) 700px, 100vw',
          'sizes_image_styles' => [],
        ],
    ]);
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '',
          'sizes_image_styles' => [
            'large' => 'large',
          ],
        ],
    ]);
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'large',
    ]);
    $this->assertTrue($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '(min-width:700px) 700px, 100vw',
          'sizes_image_styles' => [
            'large' => 'large',
          ],
        ],
    ]);
    $this->assertTrue($entity->hasImageStyleMappings());
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::getImageStyleMapping
   */
  public function testGetImageStyleMapping() {
    $entity = new ResponsiveImageStyle(['']);
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ]);
    $expected = [
      'breakpoint_id' => 'test_breakpoint',
      'multiplier' => '1x',
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ];
    $this->assertEquals($expected, $entity->getImageStyleMapping('test_breakpoint', '1x'));
    $this->assertNull($entity->getImageStyleMapping('test_unknown_breakpoint', '1x'));
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::getKeyedImageStyleMappings
   */
  public function testGetKeyedImageStyleMappings() {
    $entity = new ResponsiveImageStyle(['']);
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ]);
    $entity->addImageStyleMapping('test_breakpoint', '2x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => [
          'large' => 'large',
        ],
      ],
    ]);
    $entity->addImageStyleMapping('test_breakpoint2', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ]);
    $entity->addImageStyleMapping('test_breakpoint2', '2x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => '_original image_',
    ]);

    $expected = [
      'test_breakpoint' => [
        '1x' => [
          'breakpoint_id' => 'test_breakpoint',
          'multiplier' => '1x',
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'large',
        ],
        '2x' => [
          'breakpoint_id' => 'test_breakpoint',
          'multiplier' => '2x',
          'image_mapping_type' => 'sizes',
          'image_mapping' => [
            'sizes' => '(min-width:700px) 700px, 100vw',
            'sizes_image_styles' => [
              'large' => 'large',
            ],
          ],
        ],
      ],
      'test_breakpoint2' => [
        '1x' => [
          'breakpoint_id' => 'test_breakpoint2',
          'multiplier' => '1x',
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'thumbnail',
        ],
        '2x' => [
          'breakpoint_id' => 'test_breakpoint2',
          'multiplier' => '2x',
          'image_mapping_type' => 'image_style',
          'image_mapping' => '_original image_',
        ],
      ],
    ];
    $this->assertEquals($expected, $entity->getKeyedImageStyleMappings());

    // Add another mapping to ensure keyed mapping static cache is rebuilt.
    $entity->addImageStyleMapping('test_breakpoint2', '2x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'medium',
    ]);
    $expected['test_breakpoint2']['2x'] = [
      'breakpoint_id' => 'test_breakpoint2',
      'multiplier' => '2x',
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'medium',
    ];
    $this->assertEquals($expected, $entity->getKeyedImageStyleMappings());

    // Overwrite a mapping to ensure keyed mapping static cache is rebuilt.
    $entity->addImageStyleMapping('test_breakpoint2', '2x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ]);
    $expected['test_breakpoint2']['2x'] = [
      'breakpoint_id' => 'test_breakpoint2',
      'multiplier' => '2x',
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ];
    $this->assertEquals($expected, $entity->getKeyedImageStyleMappings());
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::getImageStyleMappings
   */
  public function testGetImageStyleMappings() {
    $entity = new ResponsiveImageStyle(['']);
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ]);
    $entity->addImageStyleMapping('test_breakpoint', '2x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => [
          'large' => 'large',
        ],
      ],
    ]);
    $entity->addImageStyleMapping('test_breakpoint2', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ]);

    $expected = [
      [
        'breakpoint_id' => 'test_breakpoint',
        'multiplier' => '1x',
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'large',
      ],
      [
        'breakpoint_id' => 'test_breakpoint',
        'multiplier' => '2x',
        'image_mapping_type' => 'sizes',
        'image_mapping' => [
          'sizes' => '(min-width:700px) 700px, 100vw',
          'sizes_image_styles' => [
            'large' => 'large',
          ],
        ],
      ],
      [
        'breakpoint_id' => 'test_breakpoint2',
        'multiplier' => '1x',
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'thumbnail',
      ],
    ];
    $this->assertEquals($expected, $entity->getImageStyleMappings());
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::removeImageStyleMappings
   */
  public function testRemoveImageStyleMappings() {
    $entity = new ResponsiveImageStyle(['']);
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ]);
    $entity->addImageStyleMapping('test_breakpoint', '2x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => [
          'large' => 'large',
        ],
      ],
    ]);
    $entity->addImageStyleMapping('test_breakpoint2', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ]);

    $this->assertTrue($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $this->assertEmpty($entity->getImageStyleMappings());
    $this->assertEmpty($entity->getKeyedImageStyleMappings());
    $this->assertFalse($entity->hasImageStyleMappings());
  }

  /**
   * @covers ::setBreakpointGroup
   * @covers ::getBreakpointGroup
   */
  public function testSetBreakpointGroup() {
    $entity = new ResponsiveImageStyle(['breakpoint_group' => 'test_group']);
    $entity->addImageStyleMapping('test_breakpoint', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ]);
    $entity->addImageStyleMapping('test_breakpoint', '2x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => [
          'large' => 'large',
        ],
      ],
    ]);
    $entity->addImageStyleMapping('test_breakpoint2', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ]);

    // Ensure that setting to same group does not remove mappings.
    $entity->setBreakpointGroup('test_group');
    $this->assertTrue($entity->hasImageStyleMappings());
    $this->assertEquals('test_group', $entity->getBreakpointGroup());

    // Ensure that changing the group removes mappings.
    $entity->setBreakpointGroup('test_group2');
    $this->assertEquals('test_group2', $entity->getBreakpointGroup());
    $this->assertFalse($entity->hasImageStyleMappings());
  }

}
