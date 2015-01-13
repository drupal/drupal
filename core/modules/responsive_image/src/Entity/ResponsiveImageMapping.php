<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Entity\ResponsiveImageMapping.
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
 *   handlers = {
 *     "list_builder" = "Drupal\responsive_image\ResponsiveImageMappingListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\responsive_image\ResponsiveImageMappingForm",
 *       "add" = "Drupal\responsive_image\ResponsiveImageMappingForm",
 *       "delete" = "Drupal\responsive_image\Form\ResponsiveImageMappingDeleteForm",
 *       "duplicate" = "Drupal\responsive_image\ResponsiveImageMappingForm"
 *     }
 *   },
 *   list_path = "admin/config/media/responsive-image-mapping",
 *   admin_permission = "administer responsive images",
 *   config_prefix = "mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/responsive-image-mapping/{responsive_image_mapping}",
 *     "duplicate-form" = "/admin/config/media/responsive-image-mapping/{responsive_image_mapping}/duplicate"
 *   }
 * )
 */
class ResponsiveImageMapping extends ConfigEntityBase implements ResponsiveImageMappingInterface {

  /**
   * The responsive image ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The responsive image label.
   *
   * @var string
   */
  protected $label;

  /**
   * The responsive image mappings.
   *
   * Each responsive mapping array contains the following keys:
   * - breakpoint_id
   * - multiplier
   * - image_style
   *
   * @var array
   */
  protected $mappings = array();

  /**
   * @var array
   */
  protected $keyedMappings;

  /**
   * The responsive image breakpoint group.
   *
   * @var string
   */
  protected $breakpointGroup = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type_id = 'responsive_image_mapping') {
    parent::__construct($values, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function addMapping($breakpoint_id, $multiplier, $image_style) {
    foreach ($this->mappings as &$mapping) {
      if ($mapping['breakpoint_id'] === $breakpoint_id && $mapping['multiplier'] === $multiplier) {
        $mapping['image_style'] = $image_style;
        return $this;
      }
    }
    $this->mappings[] = array(
      'breakpoint_id' => $breakpoint_id,
      'multiplier' => $multiplier,
      'image_style' => $image_style,
    );
    $this->keyedMappings = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMappings() {
    return !empty($this->mappings);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyedMappings() {
    if (!$this->keyedMappings) {
      $this->keyedMappings = array();
      foreach($this->mappings as $mapping) {
        $this->keyedMappings[$mapping['breakpoint_id']][$mapping['multiplier']] = $mapping['image_style'];
      }
    }
    return $this->keyedMappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyle($breakpoint_id, $multiplier) {
    $map = $this->getKeyedMappings();
    if (isset($map[$breakpoint_id][$multiplier])) {
      return $map[$breakpoint_id][$multiplier];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMappings() {
    return $this->get('mappings');
  }

  /**
   * {@inheritdoc}
   */
  public function setBreakpointGroup($breakpoint_group) {
    // If the breakpoint group is changed then the mappings are invalid.
    if ($breakpoint_group !== $this->breakpointGroup) {
      $this->removeMappings();
    }
    $this->set('breakpointGroup', $breakpoint_group);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpointGroup() {
    return $this->get('breakpointGroup');
  }

  /**
   * {@inheritdoc}
   */
  public function removeMappings() {
    $this->mappings = array();
    $this->keyedMappings = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $providers = \Drupal::service('breakpoint.manager')->getGroupProviders($this->breakpointGroup);
    foreach ($providers as $provider => $type) {
      $this->addDependency($type, $provider);
    }
    return $this->dependencies;
  }

}
