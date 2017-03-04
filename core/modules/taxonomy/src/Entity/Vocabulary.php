<?php

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
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
 *     "add-form" = "/admin/structure/taxonomy/manage/{taxonomy_vocabulary}/add",
 *     "delete-form" = "/admin/structure/taxonomy/manage/{taxonomy_vocabulary}/delete",
 *     "reset-form" = "/admin/structure/taxonomy/manage/{taxonomy_vocabulary}/reset",
 *     "overview-form" = "/admin/structure/taxonomy/manage/{taxonomy_vocabulary}/overview",
 *     "edit-form" = "/admin/structure/taxonomy/manage/{taxonomy_vocabulary}",
 *     "collection" = "/admin/structure/taxonomy",
 *   },
 *   config_export = {
 *     "name",
 *     "vid",
 *     "description",
 *     "hierarchy",
 *     "weight",
 *   }
 * )
 */
class Vocabulary extends ConfigEntityBundleBase implements VocabularyInterface {

  /**
   * The taxonomy vocabulary ID.
   *
   * @var string
   */
  protected $vid;

  /**
   * Name of the vocabulary.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of the vocabulary.
   *
   * @var string
   */
  protected $description;

  /**
   * The type of hierarchy allowed within the vocabulary.
   *
   * Possible values:
   * - VocabularyInterface::HIERARCHY_DISABLED: No parents.
   * - VocabularyInterface::HIERARCHY_SINGLE: Single parent.
   * - VocabularyInterface::HIERARCHY_MULTIPL: Multiple parents.
   *
   * @var int
   */
  protected $hierarchy = VocabularyInterface::HIERARCHY_DISABLED;

  /**
   * The weight of this vocabulary in relation to other vocabularies.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getHierarchy() {
    return $this->hierarchy;
  }

  /**
   * {@inheritdoc}
   */
  public function setHierarchy($hierarchy) {
    $this->hierarchy = $hierarchy;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->vid;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
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

    $vocabularies = [];
    foreach ($entities as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->id();
    }
    // Load all Taxonomy module fields and delete those which use only this
    // vocabulary.
    $field_storages = entity_load_multiple_by_properties('field_storage_config', ['module' => 'taxonomy']);
    foreach ($field_storages as $field_storage) {
      $modified_storage = FALSE;
      // Term reference fields may reference terms from more than one
      // vocabulary.
      foreach ($field_storage->getSetting('allowed_values') as $key => $allowed_value) {
        if (isset($vocabularies[$allowed_value['vocabulary']])) {
          $allowed_values = $field_storage->getSetting('allowed_values');
          unset($allowed_values[$key]);
          $field_storage->setSetting('allowed_values', $allowed_values);
          $modified_storage = TRUE;
        }
      }
      if ($modified_storage) {
        $allowed_values = $field_storage->getSetting('allowed_values');
        if (empty($allowed_values)) {
          $field_storage->delete();
        }
        else {
          // Update the field definition with the new allowed values.
          $field_storage->save();
        }
      }
    }
  }

}
