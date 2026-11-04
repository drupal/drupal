<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\Validator\Mapping\CascadingStrategy;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Mapping\TraversalStrategy;

/**
 * Validator metadata for typed data objects.
 *
 * @see \Drupal\Core\TypedData\Validation\RecursiveValidator::getMetadataFor()
 */
class TypedDataMetadata implements MetadataInterface {

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typedData
   *   The typed data object the metadata is about.
   */
  public function __construct(
    protected TypedDataInterface $typedData,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function findConstraints($group): array {
    return $this->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    return $this->typedData->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getTraversalStrategy(): int {
    return TraversalStrategy::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCascadingStrategy(): int {
    // By default, never cascade into validating referenced data structures.
    return CascadingStrategy::NONE;
  }

}
