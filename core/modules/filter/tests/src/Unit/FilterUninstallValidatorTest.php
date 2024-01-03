<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\filter\FilterUninstallValidator
 * @group filter
 */
class FilterUninstallValidatorTest extends UnitTestCase {

  /**
   * @var \Drupal\filter\FilterUninstallValidator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $filterUninstallValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->filterUninstallValidator = $this->getMockBuilder('Drupal\filter\FilterUninstallValidator')
      ->disableOriginalConstructor()
      ->onlyMethods(['getFilterDefinitionsByProvider', 'getEnabledFilterFormats'])
      ->getMock();
    $this->filterUninstallValidator->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoPlugins() {
    $this->filterUninstallValidator->expects($this->once())
      ->method('getFilterDefinitionsByProvider')
      ->willReturn([]);
    $this->filterUninstallValidator->expects($this->never())
      ->method('getEnabledFilterFormats');

    $module = $this->randomMachineName();
    $expected = [];
    $reasons = $this->filterUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoFormats() {
    $this->filterUninstallValidator->expects($this->once())
      ->method('getFilterDefinitionsByProvider')
      ->willReturn([
        'test_filter_plugin' => [
          'id' => 'test_filter_plugin',
          'provider' => 'filter_test',
        ],
      ]);
    $this->filterUninstallValidator->expects($this->once())
      ->method('getEnabledFilterFormats')
      ->willReturn([]);

    $module = $this->randomMachineName();
    $expected = [];
    $reasons = $this->filterUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoMatchingFormats() {
    $this->filterUninstallValidator->expects($this->once())
      ->method('getFilterDefinitionsByProvider')
      ->willReturn([
        'test_filter_plugin1' => [
          'id' => 'test_filter_plugin1',
          'provider' => 'filter_test',
        ],
        'test_filter_plugin2' => [
          'id' => 'test_filter_plugin2',
          'provider' => 'filter_test',
        ],
        'test_filter_plugin3' => [
          'id' => 'test_filter_plugin3',
          'provider' => 'filter_test',
        ],
        'test_filter_plugin4' => [
          'id' => 'test_filter_plugin4',
          'provider' => 'filter_test',
        ],
      ]);

    $filter_plugin_enabled = $this->getMockForAbstractClass('Drupal\filter\Plugin\FilterBase', [['status' => TRUE], '', ['provider' => 'filter_test']]);
    $filter_plugin_disabled = $this->getMockForAbstractClass('Drupal\filter\Plugin\FilterBase', [['status' => FALSE], '', ['provider' => 'filter_test']]);

    // The first format has 2 matching and enabled filters, but the loop breaks
    // after finding the first one.
    $filter_plugin_collection1 = $this->getMockBuilder('Drupal\filter\FilterPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $filter_plugin_collection1->expects($this->exactly(3))
      ->method('has')
      ->willReturnMap([
        ['test_filter_plugin1', FALSE],
        ['test_filter_plugin2', TRUE],
        ['test_filter_plugin3', TRUE],
        ['test_filter_plugin4', TRUE],
      ]);
    $filter_plugin_collection1->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['test_filter_plugin2', $filter_plugin_disabled],
        ['test_filter_plugin3', $filter_plugin_enabled],
        ['test_filter_plugin4', $filter_plugin_enabled],
      ]);

    $filter_format1 = $this->createMock('Drupal\filter\FilterFormatInterface');
    $filter_format1->expects($this->once())
      ->method('filters')
      ->willReturn($filter_plugin_collection1);
    $filter_format1->expects($this->once())
      ->method('label')
      ->willReturn('Filter Format 1 Label');

    // The second filter format only has one matching and enabled filter.
    $filter_plugin_collection2 = $this->getMockBuilder('Drupal\filter\FilterPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();
    $filter_plugin_collection2->expects($this->exactly(4))
      ->method('has')
      ->willReturnMap([
        ['test_filter_plugin1', FALSE],
        ['test_filter_plugin2', FALSE],
        ['test_filter_plugin3', FALSE],
        ['test_filter_plugin4', TRUE],
      ]);
    $filter_plugin_collection2->expects($this->exactly(1))
      ->method('get')
      ->with('test_filter_plugin4')
      ->willReturn($filter_plugin_enabled);

    $filter_format2 = $this->createMock('Drupal\filter\FilterFormatInterface');
    $filter_format2->expects($this->once())
      ->method('filters')
      ->willReturn($filter_plugin_collection2);
    $filter_format2->expects($this->once())
      ->method('label')
      ->willReturn('Filter Format 2 Label');
    $this->filterUninstallValidator->expects($this->once())
      ->method('getEnabledFilterFormats')
      ->willReturn([
        'test_filter_format1' => $filter_format1,
        'test_filter_format2' => $filter_format2,
      ]);

    $expected = [
      'Provides a filter plugin that is in use in the following filter formats: <em class="placeholder">Filter Format 1 Label, Filter Format 2 Label</em>',
    ];
    $reasons = $this->filterUninstallValidator->validate($this->randomMachineName());
    $this->assertEquals($expected, $reasons);
  }

}
