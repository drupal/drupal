<?php

namespace Drupal\Tests\Core\TypedData;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\TypedData\TypedData
 *
 * @group TypedData
 */
class TypedDataTest extends UnitTestCase {

  /**
   * @covers ::__sleep
   */
  public function testSleep() {
    $data_definition = $this->getMock(DataDefinitionInterface::class);
    /** @var \Drupal\Core\TypedData\TypedData $typed_data */
    $typed_data = $this->getMockForAbstractClass(TypedData::class, [$data_definition]);
    $string_translation = $this->getStringTranslationStub();
    $typed_data->setStringTranslation($string_translation);
    $typed_data_manager = $this->getMock(TypedDataManagerInterface::class);
    $typed_data->setTypedDataManager($typed_data_manager);
    $serialized_typed_data = serialize($typed_data);
    $this->assertNotContains(get_class($string_translation), $serialized_typed_data);
    $this->assertNotContains(get_class($typed_data_manager), $serialized_typed_data);
  }

}
