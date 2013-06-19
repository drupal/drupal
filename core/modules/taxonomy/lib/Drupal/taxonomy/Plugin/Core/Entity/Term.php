<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\Core\Entity\Term.
 */

namespace Drupal\taxonomy\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;
use Drupal\taxonomy\TermInterface;

/**
 * Defines the taxonomy term entity.
 *
 * @EntityType(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   bundle_label = @Translation("Vocabulary"),
 *   module = "taxonomy",
 *   controllers = {
 *     "storage" = "Drupal\taxonomy\TermStorageController",
 *     "render" = "Drupal\taxonomy\TermRenderController",
 *     "access" = "Drupal\taxonomy\TermAccessController",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\TermFormController"
 *     },
 *     "translation" = "Drupal\taxonomy\TermTranslationController"
 *   },
 *   base_table = "taxonomy_term_data",
 *   uri_callback = "taxonomy_term_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "bundle" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "vid"
 *   },
 *   links = {
 *     "canonical" = "/taxonomy/term/{taxonomy_term}",
 *     "edit-form" = "/taxonomy/term/{taxonomy_term}/edit"
 *   },
 *   menu_base_path = "taxonomy/term/%taxonomy_term",
 *   route_base_path = "admin/structure/taxonomy/manage/{bundle}",
 *   permission_granularity = "bundle"
 * )
 */
class Term extends EntityNG implements TermInterface {

  /**
   * The taxonomy term ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $tid;

  /**
   * The term UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The taxonomy vocabulary ID this term belongs to.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $vid;

  /**
   * Name of the term.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $name;

  /**
   * Description of the term.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $description;

  /**
   * The text format name for the term's description.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $format;

  /**
   * The weight of this term.
   *
   * This property stores the weight of this term in relation to other terms of
   * the same vocabulary.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $weight;

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
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $parent;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('tid')->value;
  }

  /**
   * Overides \Drupal\Core\Entity\EntityNG::init().
   */
  protected function init() {
    parent::init();
    unset($this->tid);
    unset($this->uuid);
    unset($this->vid);
    unset($this->name);
    unset($this->weight);
    unset($this->format);
    unset($this->description);
    unset($this->parent);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    // See if any of the term's children are about to be become orphans.
    $orphans = array();
    foreach (array_keys($entities) as $tid) {
      if ($children = taxonomy_term_load_children($tid)) {
        foreach ($children as $child) {
          // If the term has multiple parents, we don't delete it.
          $parents = taxonomy_term_load_parents($child->id());
          // Because the parent has already been deleted, the parent count might
          // be 0.
          if (count($parents) <= 1) {
            $orphans[] = $child->id();
          }
        }
      }
    }

    // Delete term hierarchy information after looking up orphans but before
    // deleting them so that their children/parent information is consistent.
    $storage_controller->deleteTermHierarchy(array_keys($entities));

    if (!empty($orphans)) {
      entity_delete_multiple('taxonomy_term', $orphans);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Only change the parents if a value is set, keep the existing values if
    // not.
    if (isset($this->parent->value)) {
      $storage_controller->deleteTermHierarchy(array($this->id()));
      $storage_controller->updateTermHierarchy($this);
    }
  }

}
