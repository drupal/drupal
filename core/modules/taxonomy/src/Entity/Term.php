<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Entity\Term.
 */

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\taxonomy\TermInterface;

/**
 * Defines the taxonomy term entity.
 *
 * @ContentEntityType(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   bundle_label = @Translation("Vocabulary"),
 *   handlers = {
 *     "storage" = "Drupal\taxonomy\TermStorage",
 *     "storage_schema" = "Drupal\taxonomy\TermStorageSchema",
 *     "view_builder" = "Drupal\taxonomy\TermViewBuilder",
 *     "access" = "Drupal\taxonomy\TermAccessControlHandler",
 *     "views_data" = "Drupal\taxonomy\TermViewsData",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\TermForm",
 *       "delete" = "Drupal\taxonomy\Form\TermDeleteForm"
 *     },
 *     "translation" = "Drupal\taxonomy\TermTranslationHandler"
 *   },
 *   base_table = "taxonomy_term_data",
 *   data_table = "taxonomy_term_field_data",
 *   uri_callback = "taxonomy_term_uri",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "bundle" = "vid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "taxonomy_vocabulary",
 *   field_ui_base_route = "entity.taxonomy_vocabulary.overview_form",
 *   common_reference_target = TRUE,
 *   links = {
 *     "canonical" = "/taxonomy/term/{taxonomy_term}",
 *     "delete-form" = "/taxonomy/term/{taxonomy_term}/delete",
 *     "edit-form" = "/taxonomy/term/{taxonomy_term}/edit",
 *   },
 *   permission_granularity = "bundle"
 * )
 */
class Term extends ContentEntityBase implements TermInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // See if any of the term's children are about to be become orphans.
    $orphans = array();
    foreach (array_keys($entities) as $tid) {
      if ($children = $storage->loadChildren($tid)) {
        foreach ($children as $child) {
          // If the term has multiple parents, we don't delete it.
          $parents = $storage->loadParents($child->id());
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
    if (isset($this->parent->target_id)) {
      $storage->deleteTermHierarchy(array($this->id()));
      $storage->updateTermHierarchy($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Term ID'))
      ->setDescription(t('The term ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The term UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Vocabulary'))
      ->setDescription(t('The vocabulary to which the term is assigned.'))
      ->setSetting('target_type', 'taxonomy_vocabulary');

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The term language code.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The term name.'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the term.'))
      ->setTranslatable(TRUE)
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

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this term in relation to other terms.'))
      ->setDefaultValue(0);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Term Parents'))
      ->setDescription(t('The parents of this term.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setCustomStorage(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the term was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
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
