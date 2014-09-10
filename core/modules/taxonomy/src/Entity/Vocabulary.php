<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Entity\Vocabulary.
 */

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ThirdPartySettingsTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Defines the taxonomy vocabulary entity.
 *
 * @ConfigEntityType(
 *   id = "taxonomy_vocabulary",
 *   label = @Translation("Taxonomy vocabulary"),
 *   handlers = {
 *     "storage" = "Drupal\taxonomy\VocabularyStorage",
 *     "list_builder" = "Drupal\taxonomy\VocabularyListBuilder",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\VocabularyForm",
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
 *     "add-form" = "entity.taxonomy_term.add_form",
 *     "delete-form" = "entity.taxonomy_vocabulary.delete_form",
 *     "reset-form" = "entity.taxonomy_vocabulary.reset_form",
 *     "overview-form" = "entity.taxonomy_vocabulary.overview_form",
 *     "edit-form" = "entity.taxonomy_vocabulary.edit_form"
 *   }
 * )
 */
class Vocabulary extends ConfigEntityBundleBase implements VocabularyInterface {
  use ThirdPartySettingsTrait;

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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id() && !$this->isSyncing()) {
      // Reflect machine name changes in the definitions of existing 'taxonomy'
      // fields.
      $field_ids = array();
      $field_map = \Drupal::entityManager()->getFieldMapByFieldType('taxonomy_term_reference');
      foreach ($field_map as $entity_type => $fields) {
        foreach ($fields as $field => $info) {
          $field_ids[] = $entity_type . '.' . $field;
        }
      }

      $fields = \Drupal::entityManager()->getStorage('field_storage_config')->loadMultiple($field_ids);

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
    }
    $storage->resetCache($update ? array($this->getOriginalId()) : array());
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Only load terms without a parent, child terms will get deleted too.
    entity_delete_multiple('taxonomy_term', $storage->getToplevelTids(array_keys($entities)));
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Reset caches.
    $storage->resetCache(array_keys($entities));

    if (reset($entities)->isSyncing()) {
      return;
    }

    $vocabularies = array();
    foreach ($entities as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->id();
    }
    // Load all Taxonomy module fields and delete those which use only this
    // vocabulary.
    $taxonomy_fields = entity_load_multiple_by_properties('field_storage_config', array('module' => 'taxonomy'));
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
  }

}
