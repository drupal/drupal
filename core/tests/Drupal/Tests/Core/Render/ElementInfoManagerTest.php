<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Render\ElementInfoManager.
 */
#[CoversClass(ElementInfoManager::class)]
#[Group('Render')]
class ElementInfoManagerTest extends UnitTestCase {

  /**
   * Tests the getInfo() method when render element plugins are used.
   *
   * @legacy-covers ::getInfo
   * @legacy-covers ::buildInfo
   */
  #[DataProvider('providerTestGetInfoElementPlugin')]
  public function testGetInfoElementPlugin(string $plugin_class, $expected_info): void {
    // Override the module handler to set expectations.
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->once())
      ->method('alter')
      ->with('element_info', $this->anything())
      ->willReturnArgument(0);

    $plugin = $this->createMock($plugin_class);
    $plugin->expects($this->once())
      ->method('getInfo')
      ->willReturn([
        '#theme' => 'page',
      ]);

    $themeManager = $this->createStub(ThemeManagerInterface::class);

    $element_info = $this->getMockBuilder('Drupal\Core\Render\ElementInfoManager')
      ->setConstructorArgs([
        new \ArrayObject(),
        $this->createStub(CacheBackendInterface::class),
        $this->createStub(ThemeHandlerInterface::class),
        $moduleHandler,
        $themeManager,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    $themeManager
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
   *   An array of test data for testGetInfoElementPlugin().
   */
  public static function providerTestGetInfoElementPlugin(): array {
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
   * Tests that a plugin's own #value_callback is not overwritten.
   */
  public function testGetInfoElementPluginRespectsDeclaredValueCallback(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->once())
      ->method('alter')
      ->with('element_info', $this->anything())
      ->willReturnArgument(0);

    $plugin = $this->createMock('Drupal\Core\Render\Element\FormElementInterface');
    $plugin->expects($this->once())
      ->method('getInfo')
      ->willReturn([
        '#theme' => 'page',
        '#value_callback' => 'custom_value_callback',
      ]);

    $themeManager = $this->createStub(ThemeManagerInterface::class);
    $themeManager
      ->method('getActiveTheme')
      ->willReturn(new ActiveTheme(['name' => 'test']));

    $element_info = $this->getMockBuilder('Drupal\Core\Render\ElementInfoManager')
      ->setConstructorArgs([
        new \ArrayObject(),
        $this->createStub(CacheBackendInterface::class),
        $this->createStub(ThemeHandlerInterface::class),
        $moduleHandler,
        $themeManager,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    $element_info->expects($this->once())
      ->method('createInstance')
      ->with('page')
      ->willReturn($plugin);
    $element_info->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'page' => ['class' => 'TestElementPlugin'],
      ]);

    $this->assertEquals([
      '#type' => 'page',
      '#theme' => 'page',
      '#value_callback' => 'custom_value_callback',
      '#input' => TRUE,
      '#defaults_loaded' => TRUE,
    ], $element_info->getInfo('page'));
  }

  /**
   * Tests get info property.
   */
  public function testGetInfoProperty(): void {
    $themeManager = $this->createStub(ThemeManagerInterface::class);
    $themeManager
      ->method('getActiveTheme')
      ->willReturn(new ActiveTheme(['name' => 'test']));

    $element_info = new TestElementInfoManager(
      new \ArrayObject(),
      $this->createStub(CacheBackendInterface::class),
      $this->createStub(ThemeHandlerInterface::class),
      $this->createStub(ModuleHandlerInterface::class),
      $themeManager,
    );
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
