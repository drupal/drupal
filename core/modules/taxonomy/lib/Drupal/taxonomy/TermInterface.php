<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermInterface.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a taxonomy term entity.
 */
interface TermInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the term's description.
   *
   * @return string
   *   The term description.
   */
  public function getDescription();

  /**
   * Sets the term's description.
   *
   * @param string $description
   *   The term's description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the text format name for the term's description.
   *
   * @return string
   *   The text format name.
   */
  public function getFormat();

  /**
   * Sets the text format name for the term's description.
   *
   * @param string $format
   *   The term's decription text format.
   *
   * @return $this
   */
  public function setFormat($format);

  /**
   * Gets the name of the term.
   *
   * @return string
   *   The name of the term.
   */
  public function getName();

  /**
   * Sets the name of the term.
   *
   * @param int $name
   *   The term's name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the weight of this term.
   *
   * @return int
   *   The weight of the term.
   */
  public function getWeight();

  /**
   * Gets the weight of this term.
   *
   * @param int $weight
   *   The term's weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Get the taxonomy vocabulary id this term belongs to.
   *
   * @return int
   *   The id of the vocabulary.
   */
  public function getVocabularyId();

}
