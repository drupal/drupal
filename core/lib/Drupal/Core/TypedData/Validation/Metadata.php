<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\Metadata.
 */

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Symfony\Component\Validator\ValidationVisitorInterface;
use Symfony\Component\Validator\PropertyMetadataInterface;

/**
 * Typed data implementation of the validator MetadataInterface.
 */
class Metadata implements PropertyMetadataInterface {

  /**
   * The name of the property, or empty if this is the root.
   *
   * @var string
   */
  protected $name;

  /**
   * The typed data object the metadata is about.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $typedData;

  /**
   * The metadata factory used.
   *
   * @var \Drupal\Core\TypedData\Validation\MetadataFactory
   */
  protected $factory;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data
   *   The typed data object the metadata is about.
   * @param $name
   *   The name of the property to get metadata for. Leave empty, if
   *   the data is the root of the typed data tree.
   * @param \Drupal\Core\TypedData\Validation\MetadataFactory $factory
   *   The factory to use for instantiating property metadata.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   The typed data manager.
   */
  public function __construct(TypedDataInterface $typed_data, $name = '', MetadataFactory $factory, TypedDataManager $typed_data_manager) {
    $this->typedData = $typed_data;
    $this->name = $name;
    $this->factory = $factory;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * Implements MetadataInterface::accept().
   */
  public function accept(ValidationVisitorInterface $visitor, $typed_data, $group, $propertyPath) {

    // @todo: Do we have to care about groups? Symfony class metadata has
    // $propagatedGroup.

    $visitor->visit($this, $this->typedDataManager->getCanonicalRepresentation($typed_data), $group, $propertyPath);
  }

  /**
   * Implements MetadataInterface::findConstraints().
   */
  public function findConstraints($group) {
    return $this->typedData->getConstraints();
  }

  /**
   * Returns the name of the property.
   *
   * @return string The property name.
   */
  public function getPropertyName() {
    return $this->name;
  }

  /**
   * Extracts the value of the property from the given container.
   *
   * @param mixed $container The container to extract the property value from.
   *
   * @return mixed The value of the property.
   */
  public function getPropertyValue($container) {
    return $this->typedDataManager->getCanonicalRepresentation($this->typedData);
  }

  /**
   * Returns the typed data object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The typed data object.
   */
  public function getTypedData() {
    return $this->typedData;
  }
}
