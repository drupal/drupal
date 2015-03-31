<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\PropertyContainerMetadata.
 */

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Symfony\Component\Validator\PropertyMetadataContainerInterface;
use Symfony\Component\Validator\ValidationVisitorInterface;

/**
 * Typed data implementation of the validator MetadataInterface.
 */
class PropertyContainerMetadata extends Metadata implements PropertyMetadataContainerInterface {

  /**
   * Overrides Metadata::accept().
   */
  public function accept(ValidationVisitorInterface $visitor, $typed_data, $group, $propertyPath) {
    // To let all constraints properly handle empty structures, pass on NULL
    // if the data structure is empty. That way existing NotNull or NotBlank
    // constraints work as expected.
    if ($typed_data->isEmpty()) {
      $data = NULL;
    }
    else {
      $data = $this->typedDataManager->getCanonicalRepresentation($typed_data);
    }
    $visitor->visit($this, $data, $group, $propertyPath);
    $pathPrefix = isset($propertyPath) && $propertyPath !== '' ? $propertyPath . '.' : '';

    // Only continue validating if the data is not empty.
    if ($data) {
      foreach ($typed_data as $name => $data) {
        $metadata = $this->factory->getMetadataFor($data, $name);
        $metadata->accept($visitor, $data, $group, $pathPrefix . $name);
      }
    }
  }

  /**
   * Implements PropertyMetadataContainerInterface::hasPropertyMetadata().
   */
  public function hasPropertyMetadata($property_name) {
    try {
      $exists = (bool)$this->getPropertyMetadata($property_name);
    }
    catch (\LogicException $e) {
      $exists = FALSE;
    }
    return $exists;
  }

  /**
   * Implements PropertyMetadataContainerInterface::getPropertyMetadata().
   */
  public function getPropertyMetadata($property_name) {
    if ($this->typedData instanceof ListInterface) {
      return array(new Metadata($this->typedData[$property_name], $property_name, $this->factory, $this->typedDataManager));
    }
    elseif ($this->typedData instanceof ComplexDataInterface) {
      return array(new Metadata($this->typedData->get($property_name), $property_name, $this->factory, $this->typedDataManager));
    }
    else {
      throw new \LogicException("There are no known properties.");
    }
  }
}
