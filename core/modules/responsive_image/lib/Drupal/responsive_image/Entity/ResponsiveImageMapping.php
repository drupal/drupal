<?php

/**
 * @file
 * Definition of Drupal\responsive_image\ResponsiveImageMapping.
 */

namespace Drupal\responsive_image\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\responsive_image\ResponsiveImageMappingInterface;

/**
 * Defines the responsive image mapping entity.
 *
 * @ConfigEntityType(
 *   id = "responsive_image_mapping",
 *   label = @Translation("Responsive image mapping"),
 *   controllers = {
 *     "list" = "Drupal\responsive_image\ResponsiveImageMappingListController",
 *     "form" = {
 *       "edit" = "Drupal\responsive_image\ResponsiveImageMappingFormController",
 *       "add" = "Drupal\responsive_image\ResponsiveImageMappingFormController",
 *       "delete" = "Drupal\responsive_image\Form\ResponsiveImageMappingDeleteForm",
 *       "duplicate" = "Drupal\responsive_image\ResponsiveImageMappingFormController"
 *     }
 *   },
 *   list_path = "admin/config/media/responsive-image-mapping",
 *   admin_permission = "administer responsive image",
 *   config_prefix = "mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "responsive_image.mapping_page_edit",
 *     "duplicate-form" = "responsive_image.mapping_page_duplicate"
 *   }
 * )
 */
class ResponsiveImageMapping extends ConfigEntityBase implements ResponsiveImageMappingInterface {

  /**
   * The responsive image ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The responsive image label.
   *
   * @var string
   */
  public $label;

  /**
   * The responsive image mappings.
   *
   * @var array
   */
  public $mappings = array();

  /**
   * The responsive image breakpoint group.
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

    // Split the breakpoint ids into their different parts, as dots as
    // identifiers are not possible.
    $loaded_mappings = $this->mappings;
    $this->mappings = array();
    foreach ($loaded_mappings as $breakpoint_id => $mapping) {
      list($source_type, $source, $name) = explode('.', $breakpoint_id);
      $this->mappings[$source_type][$source][$name] = $mapping;
    }

    parent::save();
    $this->loadBreakpointGroup();
    $this->loadAllMappings();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    return entity_create('responsive_image_mapping', array(
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
        // Get the components of the breakpoint ID to match the format of the
        // configuration file.
        list($source_type, $source, $name) = explode('.', $breakpoint_id);

        // Get the mapping for the default multiplier.
        $this->mappings[$breakpoint_id]['1x'] = '';
        if (isset($loaded_mappings[$source_type][$source][$name]['1x'])) {
          $this->mappings[$breakpoint_id]['1x'] = $loaded_mappings[$source_type][$source][$name]['1x'];
        }

        // Get the mapping for the other multipliers.
        if (isset($breakpoint->multipliers) && !empty($breakpoint->multipliers)) {
          foreach ($breakpoint->multipliers as $multiplier => $status) {
            if ($status) {
              $this->mappings[$breakpoint_id][$multiplier] = '';
              if (isset($loaded_mappings[$source_type][$source][$name][$multiplier])) {
                $this->mappings[$breakpoint_id][$multiplier] = $loaded_mappings[$source_type][$source][$name][$multiplier];
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
