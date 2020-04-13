<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\ElementInfoManagerTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\ElementInfoManager
 * @group Render
 */
class ElementInfoManagerTest extends UnitTestCase {

  /**
   * The mocked element_info.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeManager = $this->createMock('Drupal\Core\Theme\ThemeManagerInterface');

    $this->elementInfo = new ElementInfoManager(new \ArrayObject(), $this->cache, $this->cacheTagsInvalidator, $this->moduleHandler, $this->themeManager);
  }

  /**
   * Tests the getInfo() method when render element plugins are used.
   *
   * @covers ::getInfo
   * @covers ::buildInfo
   *
   * @dataProvider providerTestGetInfoElementPlugin
   */
  public function testGetInfoElementPlugin($plugin_class, $expected_info) {
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('element_info', $this->anything())
      ->will($this->returnArgument(0));

    $plugin = $this->createMock($plugin_class);
    $plugin->expects($this->once())
      ->method('getInfo')
      ->willReturn([
        '#theme' => 'page',
      ]);

    $element_info = $this->getMockBuilder('Drupal\Core\Render\ElementInfoManager')
      ->setConstructorArgs([new \ArrayObject(), $this->cache, $this->cacheTagsInvalidator, $this->moduleHandler, $this->themeManager])
      ->setMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    $this->themeManager->expects($this->any())
      ->method('getActiveTheme')
      ->willReturn(new ActiveTheme(['name' => 'test']));

    $element_info->expects($this->once())
      ->method('createInstance')
      ->with('page')
      ->willReturn($plugin);
    $element_info->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'page' => ['class' => 'TestElementPlugin'],
      ]);

    $this->assertEquals($expected_info, $element_info->getInfo('page'));
  }

  /**
   * Provides tests data for testGetInfoElementPlugin().
   *
   * @return array
   */
  public function providerTestGetInfoElementPlugin() {
    $data = [];
    $data[] = [
      'Drupal\Core\Render\Element\ElementInterface',
      [
        '#type' => 'page',
        '#theme' => 'page',
        '#defaults_loaded' => TRUE,
      ],
    ];

    $data[] = [
      'Drupal\Core\Render\Element\FormElementInterface',
      [
        '#type' => 'page',
        '#theme' => 'page',
        '#input' => TRUE,
        '#value_callback' => ['TestElementPlugin', 'valueCallback'],
        '#defaults_loaded' => TRUE,
      ],
    ];
    return $data;
  }

  /**
   * @covers ::getInfoProperty
   */
  public function testGetInfoProperty() {
    $this->themeManager
      ->method('getActiveTheme')
      ->willReturn(new ActiveTheme(['name' => 'test']));

    $element_info = new TestElementInfoManager(new \ArrayObject(), $this->cache, $this->cacheTagsInvalidator, $this->moduleHandler, $this->themeManager);
    $this->assertSame('baz', $element_info->getInfoProperty('foo', '#bar'));
    $this->assertNull($element_info->getInfoProperty('foo', '#non_existing_property'));
    $this->assertSame('qux', $element_info->getInfoProperty('foo', '#non_existing_property', 'qux'));
  }

}

/**
 * Provides a test custom element plugin.
 */
class TestElementInfoManager extends ElementInfoManager {

  /**
   * {@inheritdoc}
   */
  protected $elementInfo = [
    'test' => [
      'foo' => [
        '#bar' => 'baz',
      ],
    ],
  ];

}
