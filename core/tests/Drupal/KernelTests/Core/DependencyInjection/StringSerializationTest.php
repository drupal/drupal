<?php

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests serialization of string services.
 *
 * @group DependencyInjection
 * @group legacy
 */
class StringSerializationTest extends KernelTestBase {

  /**
   * Tests that strings are not put into the container class mapping.
   */
  public function testSerializeString() {
    $this->assertIsString($this->container->get('app.root'));
    $this->container->getServiceIdMappings();
    $this->assertIsString($this->container->get('app.root'));
  }

}
