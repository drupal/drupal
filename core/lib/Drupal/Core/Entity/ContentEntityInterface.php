<?php

namespace Drupal\Core\Entity;

/**
 * Defines a common interface for all content entity objects.
 *
 * Content entities use fields for all their entity properties and can be
 * translatable and revisionable. Translations and revisions can be
 * enabled per entity type through annotation and using entity type hooks.
 *
 * It's best practice to always implement ContentEntityInterface for
 * content-like entities that should be stored in some database, and
 * enable/disable revisions and translations as desired.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\Entity\ContentEntityBase
 * @see \Drupal\Core\Entity\EntityTypeInterface
 *
 * @ingroup entity_api
 */
interface ContentEntityInterface extends \Traversable, FieldableEntityInterface, TranslatableRevisionableInterface {

}
