<?php

namespace Drupal\Tests\migrate\Unit\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\Config
 * @group migrate
 */
class ConfigTest extends UnitTestCase {

  /**
   * Test the import method.
   */
  public function testImport() {
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
        ->will($this->returnValue($config));
    }
    $config->expects($this->once())
      ->method('save');
    $config->expects($this->once())
      ->method('getName')
      ->willReturn('d8_config');
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->with('d8_config')
      ->will($this->returnValue($config));
    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $row->expects($this->any())
      ->method('getRawDestination')
      ->will($this->returnValue($source));
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->never())
      ->method('getLanguageConfigOverride')
      ->with('fr', 'd8_config')
      ->will($this->returnValue($config));
    $destination = new Config(['config_name' => 'd8_config'], 'd8_config', ['pluginId' => 'd8_config'], $migration, $config_factory, $language_manager);
    $destination_id = $destination->import($row);
    $this->assertEquals($destination_id, ['d8_config']);
  }

  /**
   * Test the import method.
   */
  public function testLanguageImport() {
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
        ->will($this->returnValue($config));
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
      ->will($this->returnValue($config));
    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $row->expects($this->any())
      ->method('getRawDestination')
      ->will($this->returnValue($source));
    $row->expects($this->any())
      ->method('getDestinationProperty')
      ->will($this->returnValue($source['langcode']));
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguageConfigOverride')
      ->with('mi', 'd8_config')
      ->will($this->returnValue($config));
    $destination = new Config(['config_name' => 'd8_config', 'translations' => 'true'], 'd8_config', ['pluginId' => 'd8_config'], $migration, $config_factory, $language_manager);
    $destination_id = $destination->import($row);
    $this->assertEquals($destination_id, ['d8_config', 'mi']);
  }

}
