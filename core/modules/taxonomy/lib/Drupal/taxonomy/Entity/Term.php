<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Entity\Term.
 */

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
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
 *   controllers = {
 *     "storage" = "Drupal\taxonomy\TermStorageController",
 *     "view_builder" = "Drupal\taxonomy\TermViewBuilder",
 *     "access" = "Drupal\taxonomy\TermAccessController",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\TermFormController",
 *       "delete" = "Drupal\taxonomy\Form\TermDeleteForm"
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
 *   route_base_path = "admin/structure/taxonomy/manage/{bundle}",
 *   permission_granularity = "bundle"
 * )
 */
class Term extends ContentEntityBase implements TermInterface {

  /**
   * The taxonomy term ID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $tid;

  /**
   * The term UUID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * The taxonomy vocabulary ID this term belongs to.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $vid;

  /**
   * Name of the term.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $name;

  /**
   * Description of the term.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $description;

  /**
   * The text format name for the term's description.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $format;

  /**
   * The weight of this term.
   *
   * This property stores the weight of this term in relation to other terms of
   * the same vocabulary.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
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
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $parent;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('tid')->value;
  }

  /**
   * {@inheritdoc}
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
    parent::postDelete($storage_controller, $entities);

    // See if any of the term's children are about to be become orphans.
    $orphans = array();
    foreach (array_keys($entities) as $tid) {
      if ($children = taxonomy_term_load_children($tid)) {
        foreach ($children as $child) {
          // If the term has multiple parents, we don't delete it.
          $parents = taxonomy_term_load_parents($child->id());
          if (empty($parents)) {
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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    // Before saving the term, set changed time.
    $this->changed->value = REQUEST_TIME;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    // Only change the parents if a value is set, keep the existing values if
    // not.
    if (isset($this->parent->value)) {
      $storage_controller->deleteTermHierarchy(array($this->id()));
      $storage_controller->updateTermHierarchy($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['tid'] = array(
      'label' => t('Term ID'),
      'description' => t('The term ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The term UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
    );
    $properties['vid'] = array(
      'label' => t('Vocabulary ID'),
      'description' => t('The ID of the vocabulary to which the term is assigned.'),
      'type' => 'string_field',
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The term language code.'),
      'type' => 'language_field',
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t('The term name.'),
      'type' => 'string_field',
    );
    $properties['description'] = array(
      'label' => t('Description'),
      'description' => t('A description of the term'),
      'type' => 'string_field',
    );
    // @todo Combine with description.
    $properties['format'] = array(
      'label' => t('Description format'),
      'description' => t('The filter format ID of the description.'),
      'type' => 'string_field',
    );
    $properties['weight'] = array(
      'label' => t('Weight'),
      'description' => t('The weight of this term in relation to other terms.'),
      'type' => 'integer_field',
      'settings' => array('default_value' => 0),
    );
    $properties['parent'] = array(
      'label' => t('Term Parents'),
      'description' => t('The parents of this term.'),
      'type' => 'integer_field',
      // Save new terms with no parents by default.
      'settings' => array('default_value' => 0),
      'computed' => TRUE,
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the term was last edited.'),
      'type' => 'integer_field',
      'property_constraints' => array(
        'value' => array('EntityChanged' => array()),
      ),
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->changed->value;
  }

}
