<?php

namespace Drupal\Core\Entity;

/**
 * A storage that supports content entity types.
 */
interface ContentEntityStorageInterface extends EntityStorageInterface, RevisionableStorageInterface {

  /**
   * Constructs a new entity translation object, without permanently saving it.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object being translated.
   * @param string $langcode
   *   The translation language code.
   * @param array $values
   *   (optional) An associative array of initial field values keyed by field
   *   name. If none is provided default values will be applied.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   A new entity translation object.
   */
  public function createTranslation(ContentEntityInterface $entity, $langcode, array $values = []);


  /**
   * Creates an entity with sample field values.
   *
   * @param string|bool $bundle
   *   (optional) The entity bundle.
   * @param array $values
   *   (optional) Any default values to use during generation.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   A fieldable content entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the bundle does not exist or was needed but not specified.
   */
  public function createWithSampleValues($bundle = FALSE, array $values = []);

}
