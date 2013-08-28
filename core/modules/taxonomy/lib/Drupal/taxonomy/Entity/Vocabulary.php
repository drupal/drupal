<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Entity\Vocabulary.
 */

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Defines the taxonomy vocabulary entity.
 *
 * @EntityType(
 *   id = "taxonomy_vocabulary",
 *   label = @Translation("Taxonomy vocabulary"),
 *   module = "taxonomy",
 *   controllers = {
 *     "storage" = "Drupal\taxonomy\VocabularyStorageController",
 *     "access" = "Drupal\taxonomy\VocabularyAccessController",
 *     "list" = "Drupal\taxonomy\VocabularyListController",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\VocabularyFormController",
 *       "reset" = "Drupal\taxonomy\Form\VocabularyResetForm",
 *       "delete" = "Drupal\taxonomy\Form\VocabularyDeleteForm"
 *     }
 *   },
 *   config_prefix = "taxonomy.vocabulary",
 *   bundle_of = "taxonomy_term",
 *   entity_keys = {
 *     "id" = "vid",
 *     "label" = "name",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
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
   * The vocabulary UUID.
   *
   * @var string
   */
  public $uuid;

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
  public function uri() {
    return array(
      'path' => 'admin/structure/taxonomy/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    if (!$update) {
      entity_invoke_bundle_hook('create', 'taxonomy_term', $this->id());
    }
    elseif ($this->getOriginalID() != $this->id()) {
      // Reflect machine name changes in the definitions of existing 'taxonomy'
      // fields.
      $fields = field_read_fields();
      foreach ($fields as $field_name => $field) {
        $update_field = FALSE;
        if ($field['type'] == 'taxonomy_term_reference') {
          foreach ($field['settings']['allowed_values'] as $key => &$value) {
            if ($value['vocabulary'] == $this->getOriginalID()) {
              $value['vocabulary'] = $this->id();
              $update_field = TRUE;
            }
          }
          if ($update_field) {
            $field->save();
          }
        }
      }
      // Update bundles.
      entity_invoke_bundle_hook('rename', 'taxonomy_term', $this->getOriginalID(), $this->id());
    }
    $storage_controller->resetCache($update ? array($this->getOriginalID()) : array());
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    // Only load terms without a parent, child terms will get deleted too.
    entity_delete_multiple('taxonomy_term', $storage_controller->getToplevelTids(array_keys($entities)));
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    $vocabularies = array();
    foreach ($entities as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->id();
    }
    // Load all Taxonomy module fields and delete those which use only this
    // vocabulary.
    $taxonomy_fields = field_read_fields(array('module' => 'taxonomy'));
    foreach ($taxonomy_fields as $field_name => $taxonomy_field) {
      $modified_field = FALSE;
      // Term reference fields may reference terms from more than one
      // vocabulary.
      foreach ($taxonomy_field['settings']['allowed_values'] as $key => $allowed_value) {
        if (isset($vocabularies[$allowed_value['vocabulary']])) {
          unset($taxonomy_field['settings']['allowed_values'][$key]);
          $modified_field = TRUE;
        }
      }
      if ($modified_field) {
        if (empty($taxonomy_field['settings']['allowed_values'])) {
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
