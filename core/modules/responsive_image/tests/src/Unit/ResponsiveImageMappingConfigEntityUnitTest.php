<?php

/**
 * @file
 * Contains \Drupal\Tests\responsive_image\Unit\ResponsiveImageMappingConfigEntityUnitTest.
 */

namespace Drupal\Tests\responsive_image\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\responsive_image\Entity\ResponsiveImageMapping;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\responsive_image\Entity\ResponsiveImageMapping
 * @group block
 */
class ResponsiveImageMappingConfigEntityUnitTest extends UnitTestCase {

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
      ->with('responsive_image_mapping')
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
    $entity = new ResponsiveImageMapping(array('breakpointGroup' => 'test_group'));
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
   * @covers ::addMapping
   * @covers ::hasMapping
   */
  public function testHasMappings() {
    $entity = new ResponsiveImageMapping(array());
    $this->assertFalse($entity->hasMappings());
    $entity->addMapping('test_breakpoint', '1x', 'test_style');
    $this->assertTrue($entity->hasMappings());
  }

  /**
   * @covers ::addMapping
   * @covers ::getImageStyle
   */
  public function testGetImageStyle() {
    $entity = new ResponsiveImageMapping(array(''));
    $entity->addMapping('test_breakpoint', '1x', 'test_style');
    $this->assertEquals('test_style', $entity->getImageStyle('test_breakpoint', '1x'));
    $this->assertNull($entity->getImageStyle('test_unknown_breakpoint', '1x'));
  }

  /**
   * @covers ::addMapping
   * @covers ::getMappings
   */
  public function testGetKeyedMappings() {
    $entity = new ResponsiveImageMapping(array(''));
    $entity->addMapping('test_breakpoint', '1x', 'test_style');
    $entity->addMapping('test_breakpoint', '2x', 'test_style2');
    $entity->addMapping('test_breakpoint2', '1x', 'test_style3');

    $expected = array(
      'test_breakpoint' => array(
        '1x' => 'test_style',
        '2x' => 'test_style2',
      ),
      'test_breakpoint2' => array(
        '1x' => 'test_style3',
      )
    );
    $this->assertEquals($expected, $entity->getKeyedMappings());

    // Add another mapping to ensure keyed mapping static cache is rebuilt.
    $entity->addMapping('test_breakpoint2', '2x', 'test_style4');
    $expected['test_breakpoint2']['2x'] = 'test_style4';
    $this->assertEquals($expected, $entity->getKeyedMappings());
  }

  /**
   * @covers ::addMapping
   * @covers ::getMappings
   */
  public function testGetMappings() {
    $entity = new ResponsiveImageMapping(array(''));
    $entity->addMapping('test_breakpoint', '1x', 'test_style');
    $entity->addMapping('test_breakpoint', '2x', 'test_style2');
    $entity->addMapping('test_breakpoint2', '1x', 'test_style3');

    $expected = array(
      array(
        'breakpoint_id' => 'test_breakpoint',
        'multiplier' => '1x',
        'image_style' => 'test_style',
      ),
      array(
        'breakpoint_id' => 'test_breakpoint',
        'multiplier' => '2x',
        'image_style' => 'test_style2',
      ),
      array(
        'breakpoint_id' => 'test_breakpoint2',
        'multiplier' => '1x',
        'image_style' => 'test_style3',
      ),
    );
    $this->assertEquals($expected, $entity->getMappings());
  }

  /**
   * @covers ::addMapping
   * @covers ::removeMappings
   */
  public function testRemoveMappings() {
    $entity = new ResponsiveImageMapping(array(''));
    $entity->addMapping('test_breakpoint', '1x', 'test_style');
    $entity->addMapping('test_breakpoint', '2x', 'test_style2');
    $entity->addMapping('test_breakpoint2', '1x', 'test_style3');

    $this->assertTrue($entity->hasMappings());
    $entity->removeMappings();
    $this->assertEmpty($entity->getMappings());
    $this->assertEmpty($entity->getKeyedMappings());
    $this->assertFalse($entity->hasMappings());
  }

  /**
   * @covers ::setBreakpointGroup
   * @covers ::getBreakpointGroup
   */
  public function testSetBreakpointGroup() {
    $entity = new ResponsiveImageMapping(array('breakpointGroup' => 'test_group'));
    $entity->addMapping('test_breakpoint', '1x', 'test_style');
    $entity->addMapping('test_breakpoint', '2x', 'test_style2');
    $entity->addMapping('test_breakpoint2', '1x', 'test_style3');

    // Ensure that setting to same group does not remove mappings.
    $entity->setBreakpointGroup('test_group');
    $this->assertTrue($entity->hasMappings());
    $this->assertEquals('test_group', $entity->getBreakpointGroup());

    // Ensure that changing the group removes mappings.
    $entity->setBreakpointGroup('test_group2');
    $this->assertEquals('test_group2', $entity->getBreakpointGroup());
    $this->assertFalse($entity->hasMappings());
  }

}
