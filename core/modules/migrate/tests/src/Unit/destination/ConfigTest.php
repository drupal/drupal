<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\destination\ConfigTest.
 */

namespace Drupal\Tests\migrate\Unit\destination;

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
    $source = array(
      'test' => 'x',
    );
    $migration = $this->getMockBuilder('Drupal\migrate\Entity\Migration')
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
    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->once())
      ->method('getEditable')
      ->with('d8_config')
      ->will($this->returnValue($config));
    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $row->expects($this->once())
      ->method('getRawDestination')
      ->will($this->returnValue($source));
    $destination = new Config(array('config_name' => 'd8_config'), 'd8_config', array('pluginId' => 'd8_config'), $migration, $config_factory);
    $destination->import($row);
  }

}
