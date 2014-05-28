<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Entity\Term.
 */

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\taxonomy\TermInterface;

/**
 * Defines the taxonomy term entity.
 *
 * @ContentEntityType(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   bundle_label = @Translation("Vocabulary"),
 *   controllers = {
 *     "storage" = "Drupal\taxonomy\TermStorage",
 *     "view_builder" = "Drupal\taxonomy\TermViewBuilder",
 *     "access" = "Drupal\taxonomy\TermAccessController",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\TermForm",
 *       "delete" = "Drupal\taxonomy\Form\TermDeleteForm"
 *     },
 *     "translation" = "Drupal\taxonomy\TermTranslationHandler"
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
 *   bundle_entity_type = "taxonomy_vocabulary",
 *   links = {
 *     "canonical" = "taxonomy.term_page",
 *     "delete-form" = "taxonomy.term_delete",
 *     "edit-form" = "taxonomy.term_edit",
 *     "admin-form" = "taxonomy.overview_terms"
 *   },
 *   permission_granularity = "bundle"
 * )
 */
class Term extends ContentEntityBase implements TermInterface {

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

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
    $storage->deleteTermHierarchy(array_keys($entities));

    if (!empty($orphans)) {
      entity_delete_multiple('taxonomy_term', $orphans);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Only change the parents if a value is set, keep the existing values if
    // not.
    if (isset($this->parent->value)) {
      $storage->deleteTermHierarchy(array($this->id()));
      $storage->updateTermHierarchy($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tid'] = FieldDefinition::create('integer')
      ->setLabel(t('Term ID'))
      ->setDescription(t('The term ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The term UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Vocabulary'))
      ->setDescription(t('The vocabulary to which the term is assigned.'))
      ->setSetting('target_type', 'taxonomy_vocabulary')
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The term language code.'));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The term name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = FieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the term.'))
      ->setSetting('text_processing', 1)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = FieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this term in relation to other terms.'))
      ->setSetting('default_value', 0);

    // @todo Convert this to an entity_reference field, see
    // https://drupal.org/node/1915056
    $fields['parent'] = FieldDefinition::create('integer')
      ->setLabel(t('Term Parents'))
      ->setDescription(t('The parents of this term.'))
      // Save new terms with no parents by default.
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE)
      ->addConstraint('TermParent', array());

    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the term was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->get('description')->format;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormat($format) {
    $this->get('description')->format = $format;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabularyId() {
    return $this->get('vid')->target_id;
  }

}
