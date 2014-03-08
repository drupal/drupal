<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Entity\Vocabulary.
 */

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\field\Field;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Defines the taxonomy vocabulary entity.
 *
 * @ConfigEntityType(
 *   id = "taxonomy_vocabulary",
 *   label = @Translation("Taxonomy vocabulary"),
 *   controllers = {
 *     "storage" = "Drupal\taxonomy\VocabularyStorageController",
 *     "list" = "Drupal\taxonomy\VocabularyListController",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\VocabularyFormController",
 *       "reset" = "Drupal\taxonomy\Form\VocabularyResetForm",
 *       "delete" = "Drupal\taxonomy\Form\VocabularyDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer taxonomy",
 *   config_prefix = "vocabulary",
 *   bundle_of = "taxonomy_term",
 *   entity_keys = {
 *     "id" = "vid",
 *     "label" = "name",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "taxonomy.term_add",
 *     "delete-form" = "taxonomy.vocabulary_delete",
 *     "reset" = "taxonomy.vocabulary_reset",
 *     "overview-form" = "taxonomy.overview_terms",
 *     "edit-form" = "taxonomy.vocabulary_edit"
 *   }
 * )
 */
class Vocabulary extends ConfigEntityBase implements VocabularyInterface {

  /**
   * The taxonomy vocabulary ID.
   *
   * @var string
   */
  public $vid;

  /**
   * Name of the vocabulary.
   *
   * @var string
   */
  public $name;

  /**
   * Description of the vocabulary.
   *
   * @var string
   */
  public $description;

  /**
   * The type of hierarchy allowed within the vocabulary.
   *
   * Possible values:
   * - TAXONOMY_HIERARCHY_DISABLED: No parents.
   * - TAXONOMY_HIERARCHY_SINGLE: Single parent.
   * - TAXONOMY_HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @var integer
   */
  public $hierarchy = TAXONOMY_HIERARCHY_DISABLED;

  /**
   * The weight of this vocabulary in relation to other vocabularies.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->vid;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    if (!$update) {
      entity_invoke_bundle_hook('create', 'taxonomy_term', $this->id());
    }
    elseif ($this->getOriginalId() != $this->id()) {
      // Reflect machine name changes in the definitions of existing 'taxonomy'
      // fields.
      $field_ids = array();
      $field_map = Field::fieldInfo()->getFieldMap();
      foreach ($field_map as $entity_type => $fields) {
        foreach ($fields as $field => $info) {
          if ($info['type'] == 'taxonomy_term_reference') {
            $field_ids[] = $entity_type . '.' . $field;
          }
        }
      }

      $fields = \Drupal::entityManager()->getStorageController('field_config')->loadMultiple($field_ids);

      foreach ($fields as $field) {
        $update_field = FALSE;

        foreach ($field->settings['allowed_values'] as &$value) {
          if ($value['vocabulary'] == $this->getOriginalId()) {
            $value['vocabulary'] = $this->id();
            $update_field = TRUE;
          }
        }

        if ($update_field) {
          $field->save();
        }
      }
      // Update bundles.
      entity_invoke_bundle_hook('rename', 'taxonomy_term', $this->getOriginalId(), $this->id());
    }
    $storage_controller->resetCache($update ? array($this->getOriginalId()) : array());
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    // Only load terms without a parent, child terms will get deleted too.
    entity_delete_multiple('taxonomy_term', $storage_controller->getToplevelTids(array_keys($entities)));
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $vocabularies = array();
    foreach ($entities as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->id();
    }
    // Load all Taxonomy module fields and delete those which use only this
    // vocabulary.
    $taxonomy_fields = entity_load_multiple_by_properties('field_config', array('module' => 'taxonomy'));
    foreach ($taxonomy_fields as $taxonomy_field) {
      $modified_field = FALSE;
      // Term reference fields may reference terms from more than one
      // vocabulary.
      foreach ($taxonomy_field->settings['allowed_values'] as $key => $allowed_value) {
        if (isset($vocabularies[$allowed_value['vocabulary']])) {
          unset($taxonomy_field->settings['allowed_values'][$key]);
          $modified_field = TRUE;
        }
      }
      if ($modified_field) {
        if (empty($taxonomy_field->settings['allowed_values'])) {
          $taxonomy_field->delete();
        }
        else {
          // Update the field definition with the new allowed values.
          $taxonomy_field->save();
        }
      }
    }
    // Reset caches.
    $storage_controller->resetCache(array_keys($vocabularies));
  }

}
