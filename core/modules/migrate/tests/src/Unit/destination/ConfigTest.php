<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\destination;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\Config
 * @group migrate
 */
class ConfigTest extends UnitTestCase {

  /**
   * Tests the import method.
   */
  public function testImport(): void {
    $source = [
      'test' => 'x',
    ];
    $migration = $this->getMockBuilder('Drupal\migrate\Plugin\Migration')
      ->disableOriginalConstructor()
      ->getMock();
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    foreach ($source as $key => $val) {
      $config->expects($this->once())
        ->method('set')
        ->with($this->equalTo($key), $this->equalTo($val))
        ->willReturn($config);
    }
    $config->expects($this->once())
      ->method('save');
    $config->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn('d8_config');
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->with('d8_config')
      ->willReturn($config);
    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $row->expects($this->any())
      ->method('getRawDestination')
      ->willReturn($source);
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->never())
      ->method('getLanguageConfigOverride')
      ->with('fr', 'd8_config')
      ->willReturn($config);
    $destination = new Config(['config_name' => 'd8_config'], 'd8_config', ['pluginId' => 'd8_config'], $migration, $config_factory, $language_manager, $this->createMock(TypedConfigManagerInterface::class));
    $destination_id = $destination->import($row);
    $this->assertEquals(['d8_config'], $destination_id);
  }

  /**
   * Tests the import method.
   */
  public function testLanguageImport(): void {
    $source = [
      'langcode' => 'mi',
    ];
    $migration = $this->getMockBuilder(MigrationInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    foreach ($source as $key => $val) {
      $config->expects($this->once())
        ->method('set')
        ->with($this->equalTo($key), $this->equalTo($val))
        ->willReturn($config);
    }
    $config->expects($this->once())
      ->method('save');
    $config->expects($this->any())
      ->method('getName')
      ->willReturn('d8_config');
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->with('d8_config')
      ->willReturn($config);
    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $row->expects($this->any())
      ->method('getRawDestination')
      ->willReturn($source);
    $row->expects($this->any())
      ->method('getDestinationProperty')
      ->willReturn($source['langcode']);
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguageConfigOverride')
      ->with('mi', 'd8_config')
      ->willReturn($config);
    $destination = new Config(['config_name' => 'd8_config', 'translations' => 'true'], 'd8_config', ['pluginId' => 'd8_config'], $migration, $config_factory, $language_manager, $this->createMock(TypedConfigManagerInterface::class));
    $destination_id = $destination->import($row);
    $this->assertEquals(['d8_config', 'mi'], $destination_id);
  }

}
