<?php

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\serialization\Normalizer\ConfigEntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Normalizer\ConfigEntityNormalizer
 * @group serialization
 */
class ConfigEntityNormalizerTest extends UnitTestCase {

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $test_export_properties = ['test' => 'test'];

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $normalizer = new ConfigEntityNormalizer($entity_manager);

    $config_entity = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $config_entity->expects($this->once())
      ->method('toArray')
      ->will($this->returnValue($test_export_properties));

    $this->assertSame($test_export_properties, $normalizer->normalize($config_entity));
  }

}
