<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Validation\MetadataBase.
 */

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\Exception\BadMethodCallException;
use Symfony\Component\Validator\Mapping\CascadingStrategy;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Mapping\TraversalStrategy;
use Symfony\Component\Validator\ValidationVisitorInterface;

/**
 * Validator metadata for typed data objects.
 *
 * @see \Drupal\Core\TypedData\Validation\RecursiveValidator::getMetadataFor()
 */
class TypedDataMetadata implements MetadataInterface {

  /**
   * The typed data object the metadata is about.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $typedData;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data
   *   The typed data object the metadata is about.
   */
  public function __construct(TypedDataInterface $typed_data) {
    $this->typedData = $typed_data;
  }

  /**
   * {@inheritdoc}
   */
  public function accept(ValidationVisitorInterface $visitor, $typed_data, $group, $propertyPath) {
    throw new BadMethodCallException('Not supported.');
  }

  /**
   * {@inheritdoc}
   */
  public function findConstraints($group) {
    return $this->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return $this->typedData->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getTraversalStrategy() {
    return TraversalStrategy::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCascadingStrategy() {
    // By default, never cascade into validating referenced data structures.
    return CascadingStrategy::NONE;
  }

}
