<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\Normalizer\ConfigNormalizerTest.
 */

namespace Drupal\serialization\Tests\Normalizer;

use Drupal\serialization\Normalizer\ConfigEntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ConfigEntityNormalizer class.
 *
 * @group Serialization
 *
 * @coversDefaultClass \Drupal\serialization\Normalizer\ConfigEntityNormalizer
 */
class ConfigEntityNormalizerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'ConfigEntityNormalizer',
      'description' => 'Tests the ConfigEntityNormalizer class.',
      'group' => 'Serialization',
    );
  }

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $test_export_properties = array('test' => 'test');

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $normalizer = new ConfigEntityNormalizer($entity_manager);

    $config_entity = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $config_entity->expects($this->once())
      ->method('getExportProperties')
      ->will($this->returnValue($test_export_properties));

    $this->assertSame($test_export_properties, $normalizer->normalize($config_entity));
  }

}

