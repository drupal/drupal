<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Normalizer;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Trait that provides mocked typed data objects.
 */
trait InternalTypedDataTestTrait {

  /**
   * Gets a typed data property.
   *
   * @param bool $internal
   *   Whether the typed data property is internal.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The typed data property.
   */
  protected function getTypedDataProperty($internal = TRUE) {
    $definition = $this->prophesize(DataDefinitionInterface::class);
    $definition->isInternal()
      ->willReturn($internal)
      ->shouldBeCalled();
    $definition = $definition->reveal();

    $property = $this->prophesize(TypedDataInterface::class);
    $property->getDataDefinition()
      ->willReturn($definition)
      ->shouldBeCalled();
    return $property->reveal();
  }

}
