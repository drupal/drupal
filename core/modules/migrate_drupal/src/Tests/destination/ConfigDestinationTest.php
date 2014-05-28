<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\destination\ConfigDestinationTest.
 */

namespace Drupal\migrate_drupal\Tests\destination;

use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\Tests\UnitTestCase;

/**
 * Test the raw config destination.
 *
 * @see \Drupal\migrate_drupal\Plugin\migrate\destination\Config
 * @group Drupal
 * @group migrate_drupal
 */
class ConfigDestinationTest extends UnitTestCase {

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
    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();
    $row->expects($this->once())
      ->method('getRawDestination')
      ->will($this->returnValue($source));
    $destination = new Config(array(), 'd8_config', array('pluginId' => 'd8_config'), $migration, $config);
    $destination->import($row);
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Destination test',
      'description' => 'Tests for destination plugin.',
      'group' => 'Migrate',
    );
  }

}
