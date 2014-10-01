<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Defines a common interface for all content entity objects.
 *
 * Content entities use fields for all their entity properties and are
 * translatable and revisionable, while translations and revisions can be
 * enabled per entity type. It's best practice to always implement
 * ContentEntityInterface for content-like entities that should be stored in
 * some database, and enable/disable revisions and translations as desired.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\Entity\ContentEntityBase
 *
 * @ingroup entity_api
 */
interface ContentEntityInterface extends \Traversable, FieldableEntityInterface, RevisionableInterface, TranslatableInterface {

  /**
   * Marks the translation identified by the given language code as existing.
   *
   * @param string $langcode
   *   The language code identifying the translation to be initialized.
   *
   * @todo Remove this as soon as translation metadata have been converted to
   *    regular fields.
   */
  public function initTranslation($langcode);

}
