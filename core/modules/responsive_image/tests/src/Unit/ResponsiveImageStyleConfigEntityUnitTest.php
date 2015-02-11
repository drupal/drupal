<?php

/**
 * @file
 * Contains \Drupal\Tests\responsive_image\Unit\ResponsiveImageStyleConfigEntityUnitTest.
 */

namespace Drupal\Tests\responsive_image\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The breakpoint manager used for testing.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $breakpointManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
       ->method('getProvider')
       ->will($this->returnValue('responsive_image'));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('responsive_image_style')
      ->will($this->returnValue($this->entityType));

    $this->breakpointManager = $this->getMock('\Drupal\breakpoint\BreakpointManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('breakpoint.manager', $this->breakpointManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $entity = new ResponsiveImageStyle(array('breakpoint_group' => 'test_group'));
    $entity->setBreakpointGroup('test_group');

    $this->breakpointManager->expects($this->any())
      ->method('getGroupProviders')
      ->with('test_group')
      ->willReturn(array('bartik' => 'theme', 'toolbar' => 'module'));

    $dependencies = $entity->calculateDependencies();
    $this->assertContains('toolbar', $dependencies['module']);
    $this->assertContains('bartik', $dependencies['theme']);
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::hasImageStyleMappings
   */
  public function testHasImageStyleMappings() {
    $entity = new ResponsiveImageStyle(array());
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
        'image_mapping_type' => 'image_style',
        'image_mapping' => '',
    ));
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
        'image_mapping_type' => 'sizes',
        'image_mapping' => array(
          'sizes' => '(min-width:700px) 700px, 100vw',
          'sizes_image_styles' => array(),
        ),
    ));
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
        'image_mapping_type' => 'sizes',
        'image_mapping' => array(
          'sizes' => '',
          'sizes_image_styles' => array(
            'large' => 'large',
          ),
        ),
    ));
    $this->assertFalse($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'large',
    ));
    $this->assertTrue($entity->hasImageStyleMappings());
    $entity->removeImageStyleMappings();
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
        'image_mapping_type' => 'sizes',
        'image_mapping' => array(
          'sizes' => '(min-width:700px) 700px, 100vw',
          'sizes_image_styles' => array(
            'large' => 'large',
          ),
        ),
    ));
    $this->assertTrue($entity->hasImageStyleMappings());
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::getImageStyleMapping
   */
  public function testGetImageStyleMapping() {
    $entity = new ResponsiveImageStyle(array(''));
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ));
    $expected = array(
      'breakpoint_id' => 'test_breakpoint',
      'multiplier' => '1x',
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    );
    $this->assertEquals($expected, $entity->getImageStyleMapping('test_breakpoint', '1x'));
    $this->assertNull($entity->getImageStyleMapping('test_unknown_breakpoint', '1x'));
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::getKeyedImageStyleMappings
   */
  public function testGetKeyedImageStyleMappings() {
    $entity = new ResponsiveImageStyle(array(''));
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ));
    $entity->addImageStyleMapping('test_breakpoint', '2x', array(
      'image_mapping_type' => 'sizes',
      'image_mapping' => array(
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => array(
          'large' => 'large',
        ),
      ),
    ));
    $entity->addImageStyleMapping('test_breakpoint2', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ));

    $expected = array(
      'test_breakpoint' => array(
        '1x' => array(
          'breakpoint_id' => 'test_breakpoint',
          'multiplier' => '1x',
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'large',
        ),
        '2x' => array(
          'breakpoint_id' => 'test_breakpoint',
          'multiplier' => '2x',
          'image_mapping_type' => 'sizes',
          'image_mapping' => array(
            'sizes' => '(min-width:700px) 700px, 100vw',
            'sizes_image_styles' => array(
              'large' => 'large',
            ),
          ),
        ),
      ),
      'test_breakpoint2' => array(
        '1x' => array(
          'breakpoint_id' => 'test_breakpoint2',
          'multiplier' => '1x',
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'thumbnail',
        ),
      )
    );
    $this->assertEquals($expected, $entity->getKeyedImageStyleMappings());

    // Add another mapping to ensure keyed mapping static cache is rebuilt.
    $entity->addImageStyleMapping('test_breakpoint2', '2x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'medium',
    ));
    $expected['test_breakpoint2']['2x'] = array(
      'breakpoint_id' => 'test_breakpoint2',
      'multiplier' => '2x',
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'medium',
    );
    $this->assertEquals($expected, $entity->getKeyedImageStyleMappings());
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::getImageStyleMappings
   */
  public function testGetImageStyleMappings() {
    $entity = new ResponsiveImageStyle(array(''));
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ));
    $entity->addImageStyleMapping('test_breakpoint', '2x', array(
      'image_mapping_type' => 'sizes',
      'image_mapping' => array(
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => array(
          'large' => 'large',
        ),
      ),
    ));
    $entity->addImageStyleMapping('test_breakpoint2', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ));

    $expected = array(
      array(
        'breakpoint_id' => 'test_breakpoint',
        'multiplier' => '1x',
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'large',
      ),
      array(
        'breakpoint_id' => 'test_breakpoint',
        'multiplier' => '2x',
        'image_mapping_type' => 'sizes',
        'image_mapping' => array(
          'sizes' => '(min-width:700px) 700px, 100vw',
          'sizes_image_styles' => array(
            'large' => 'large',
          ),
        ),
      ),
      array(
        'breakpoint_id' => 'test_breakpoint2',
        'multiplier' => '1x',
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'thumbnail',
      ),
    );
    $this->assertEquals($expected, $entity->getImageStyleMappings());
  }

  /**
   * @covers ::addImageStyleMapping
   * @covers ::removeImageStyleMappings
   */
  public function testRemoveImageStyleMappings() {
    $entity = new ResponsiveImageStyle(array(''));
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ));
    $entity->addImageStyleMapping('test_breakpoint', '2x', array(
      'image_mapping_type' => 'sizes',
      'image_mapping' => array(
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => array(
          'large' => 'large',
        ),
      ),
    ));
    $entity->addImageStyleMapping('test_breakpoint2', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ));

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
    $entity = new ResponsiveImageStyle(array('breakpoint_group' => 'test_group'));
    $entity->addImageStyleMapping('test_breakpoint', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'large',
    ));
    $entity->addImageStyleMapping('test_breakpoint', '2x', array(
      'image_mapping_type' => 'sizes',
      'image_mapping' => array(
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => array(
          'large' => 'large',
        ),
      ),
    ));
    $entity->addImageStyleMapping('test_breakpoint2', '1x', array(
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'thumbnail',
    ));

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
