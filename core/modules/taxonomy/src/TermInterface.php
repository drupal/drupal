<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Provides an interface defining a taxonomy term entity.
 */
interface TermInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, RevisionLogInterface {

  /**
   * Gets the term description.
   *
   * @return string
   *   The term description.
   */
  public function getDescription();

  /**
   * Sets the term description.
   *
   * @param string $description
   *   The term description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the text format name for the term description.
   *
   * @return string
   *   The text format name.
   */
  public function getFormat();

  /**
   * Sets the text format name for the term description.
   *
   * @param string $format
   *   The text format name.
   *
   * @return $this
   */
  public function setFormat($format);

  /**
   * Gets the term name.
   *
   * @return string
   *   The term name.
   */
  public function getName();

  /**
   * Sets the term name.
   *
   * @param string $name
   *   The term name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the term weight.
   *
   * @return int
   *   The term weight.
   */
  public function getWeight();

  /**
   * Sets the term weight.
   *
   * @param int $weight
   *   The term weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the ID of the vocabulary that owns the term.
   *
   * @return string
   *   The vocabulary ID.
   *
   * @deprecated Scheduled for removal before Drupal 9.0.0. Use
   *   TermInterface::bundle() instead.
   */
  public function getVocabularyId();

}
