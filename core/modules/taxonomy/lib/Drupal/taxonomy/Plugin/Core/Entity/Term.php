<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\Core\Entity\Term.
 */

namespace Drupal\taxonomy\Plugin\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the taxonomy term entity.
 *
 * @Plugin(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   module = "taxonomy",
 *   controller_class = "Drupal\taxonomy\TermStorageController",
 *   render_controller_class = "Drupal\taxonomy\TermRenderController",
 *   form_controller_class = {
 *     "default" = "Drupal\taxonomy\TermFormController"
 *   },
 *   base_table = "taxonomy_term_data",
 *   uri_callback = "taxonomy_term_uri",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "bundle" = "vocabulary_machine_name",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "machine_name"
 *   },
 *   view_modes = {
 *     "full" = {
 *       "label" = "Taxonomy term page",
 *       "custom_settings" = FALSE
 *     }
 *   }
 * )
 */
class Term extends Entity implements ContentEntityInterface {

  /**
   * The taxonomy term ID.
   *
   * @var integer
   */
  public $tid;

  /**
   * The term UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The taxonomy vocabulary ID this term belongs to.
   *
   * @var integer
   */
  public $vid;

  /**
   * Name of the term.
   *
   * @var string
   */
  public $name;

  /**
   * Description of the term.
   *
   * @var string
   */
  public $description;

  /**
   * The text format name for the term's description.
   *
   * @var string
   */
  public $format;

  /**
   * The weight of this term.
   *
   * This property stores the weight of this term in relation to other terms of
   * the same vocabulary.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * The parent term(s) for this term.
   *
   * This property is not loaded, but may be used to modify the term parents via
   * Term::save().
   *
   * The property can be set to an array of term IDs. An entry of 0 means this
   * term does not have any parents. When omitting this variable during an
   * update, the existing hierarchy for the term remains unchanged.
   *
   * @var array
   */
  public $parent;

  /**
   * The machine name of the vocabulary the term is assigned to.
   *
   * If not given, this value will be set automatically by loading the
   * vocabulary based on the $entity->vid property.
   *
   * @var string
   */
  public $vocabulary_machine_name;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->tid;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->vocabulary_machine_name;
  }
}
