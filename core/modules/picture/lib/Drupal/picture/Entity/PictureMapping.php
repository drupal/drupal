<?php

/**
 * @file
 * Definition of Drupal\picture\PictureMapping.
 */

namespace Drupal\picture\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\picture\PictureMappingInterface;

/**
 * Defines the Picture entity.
 *
 * @EntityType(
 *   id = "picture_mapping",
 *   label = @Translation("Picture mapping"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "list" = "Drupal\picture\PictureMappingListController",
 *     "form" = {
 *       "edit" = "Drupal\picture\PictureMappingFormController",
 *       "add" = "Drupal\picture\PictureMappingFormController",
 *       "delete" = "Drupal\picture\Form\PictureMappingDeleteForm",
 *       "duplicate" = "Drupal\picture\PictureMappingFormController"
 *     }
 *   },
 *   list_path = "admin/config/media/picturemapping",
 *   admin_permission = "administer pictures",
 *   config_prefix = "picture.mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "picture.mapping_page_edit"
 *   }
 * )
 */
class PictureMapping extends ConfigEntityBase implements PictureMappingInterface {

  /**
   * The picture ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The picture UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The picture label.
   *
   * @var string
   */
  public $label;

  /**
   * The picture mappings.
   *
   * @var array
   */
  public $mappings = array();

  /**
   * The picture breakpoint group.
   *
   * @var BreakpointGroup
   */
  public $breakpointGroup = '';

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct().
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->loadBreakpointGroup();
    $this->loadAllMappings();
  }

  /**
   * Overrides Drupal\Core\Entity::save().
   */
  public function save() {
    // Only save the keys, but return the full objects.
    if (isset($this->breakpointGroup) && is_object($this->breakpointGroup)) {
      $this->breakpointGroup = $this->breakpointGroup->id();
    }
    parent::save();
    $this->loadBreakpointGroup();
    $this->loadAllMappings();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    return entity_create('picture_mapping', array(
      'id' => '',
      'label' => t('Clone of !label', array('!label' => check_plain($this->label()))),
      'mappings' => $this->mappings,
    ));
  }

  /**
   * Loads the breakpoint group.
   */
  protected function loadBreakpointGroup() {
    if ($this->breakpointGroup) {
      $breakpoint_group = entity_load('breakpoint_group', $this->breakpointGroup);
      $this->breakpointGroup = $breakpoint_group;
    }
  }

  /**
   * Loads all mappings and removes non-existing ones.
   */
  protected function loadAllMappings() {
    $loaded_mappings = $this->mappings;
    $this->mappings = array();
    if ($this->breakpointGroup) {
      foreach ($this->breakpointGroup->getBreakpoints() as $breakpoint_id => $breakpoint) {
        // Get the mapping for the default multiplier.
        $this->mappings[$breakpoint_id]['1x'] = '';
        if (isset($loaded_mappings[$breakpoint_id]['1x'])) {
          $this->mappings[$breakpoint_id]['1x'] = $loaded_mappings[$breakpoint_id]['1x'];
        }

        // Get the mapping for the other multipliers.
        if (isset($breakpoint->multipliers) && !empty($breakpoint->multipliers)) {
          foreach ($breakpoint->multipliers as $multiplier => $status) {
            if ($status) {
              $this->mappings[$breakpoint_id][$multiplier] = '';
              if (isset($loaded_mappings[$breakpoint_id][$multiplier])) {
                $this->mappings[$breakpoint_id][$multiplier] = $loaded_mappings[$breakpoint_id][$multiplier];
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasMappings() {
    $mapping_found = FALSE;
    foreach ($this->mappings as $multipliers) {
      $filtered_array = array_filter($multipliers);
      if (!empty($filtered_array)) {
        $mapping_found = TRUE;
        break;
      }
    }
    return $mapping_found;
  }
}
