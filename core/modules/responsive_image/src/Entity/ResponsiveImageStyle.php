<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Entity\ResponsiveImageStyle.
 */

namespace Drupal\responsive_image\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\ResponsiveImageStyleInterface;

/**
 * Defines the responsive image style entity.
 *
 * @ConfigEntityType(
 *   id = "responsive_image_style",
 *   label = @Translation("Responsive image style"),
 *   handlers = {
 *     "list_builder" = "Drupal\responsive_image\ResponsiveImageStyleListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\responsive_image\ResponsiveImageStyleForm",
 *       "add" = "Drupal\responsive_image\ResponsiveImageStyleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "duplicate" = "Drupal\responsive_image\ResponsiveImageStyleForm"
 *     }
 *   },
 *   list_path = "admin/config/media/responsive-image-style",
 *   admin_permission = "administer responsive images",
 *   config_prefix = "styles",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/responsive-image-style/{responsive_image_style}",
 *     "duplicate-form" = "/admin/config/media/responsive-image-style/{responsive_image_style}/duplicate",
 *     "delete-form" = "/admin/config/media/responsive-image-style/{responsive_image_style}/delete",
 *     "collection" = "/admin/config/media/responsive-image-style",
 *   }
 * )
 */
class ResponsiveImageStyle extends ConfigEntityBase implements ResponsiveImageStyleInterface {

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
   * The image style mappings.
   *
   * Each image style mapping array contains the following keys:
   *   - image_mapping_type: Either 'image_style' or 'sizes'.
   *   - image_mapping:
   *     - If image_mapping_type is 'image_style', the image style ID (a
   *       string).
   *     - If image_mapping_type is 'sizes', an array with following keys:
   *       - sizes: The value for the 'sizes' attribute.
   *       - sizes_image_styles: The image styles to use for the 'srcset'
   *         attribute.
   *   - breakpoint_id: The breakpoint ID for this image style mapping.
   *   - multiplier: The multiplier for this image style mapping.
   *
   * @var array
   */
  protected $image_style_mappings = array();

  /**
   * @var array
   */
  protected $keyedImageStyleMappings;

  /**
   * The responsive image breakpoint group.
   *
   * @var string
   */
  protected $breakpoint_group = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type_id = 'responsive_image_style') {
    parent::__construct($values, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function addImageStyleMapping($breakpoint_id, $multiplier, array $image_style_mapping) {
    // If there is an existing mapping, overwrite it.
    foreach ($this->image_style_mappings as &$mapping) {
      if ($mapping['breakpoint_id'] === $breakpoint_id && $mapping['multiplier'] === $multiplier) {
        $mapping = array(
          'breakpoint_id' => $breakpoint_id,
          'multiplier' => $multiplier,
        ) + $image_style_mapping;
        return $this;
      }
    }
    $this->image_style_mappings[] = array(
      'breakpoint_id' => $breakpoint_id,
      'multiplier' => $multiplier,
    ) + $image_style_mapping;
    $this->keyedImageStyleMappings = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasImageStyleMappings() {
    $mappings = $this->getKeyedImageStyleMappings();
    return !empty($mappings);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyedImageStyleMappings() {
    if (!$this->keyedImageStyleMappings) {
      $this->keyedImageStyleMappings = array();
      foreach($this->image_style_mappings as $mapping) {
        if (!static::isEmptyImageStyleMapping($mapping)) {
          $this->keyedImageStyleMappings[$mapping['breakpoint_id']][$mapping['multiplier']] = $mapping;
        }
      }
    }
    return $this->keyedImageStyleMappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyleMappings() {
    return $this->image_style_mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function setBreakpointGroup($breakpoint_group) {
    // If the breakpoint group is changed then the image style mappings are
    // invalid.
    if ($breakpoint_group !== $this->breakpoint_group) {
      $this->removeImageStyleMappings();
    }
    $this->breakpoint_group = $breakpoint_group;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpointGroup() {
    return $this->breakpoint_group;
  }

  /**
   * {@inheritdoc}
   */
  public function removeImageStyleMappings() {
    $this->image_style_mappings = array();
    $this->keyedImageStyleMappings = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $providers = \Drupal::service('breakpoint.manager')->getGroupProviders($this->breakpoint_group);
    foreach ($providers as $provider => $type) {
      $this->addDependency($type, $provider);
    }
    // Extract all the styles from the image style mappings.
    $styles = ImageStyle::loadMultiple($this->getImageStyleIds());
    array_walk($styles, function ($style) {
      $this->addDependency('config', $style->getConfigDependencyName());
    });
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function isEmptyImageStyleMapping(array $image_style_mapping) {
    if (!empty($image_style_mapping)) {
      switch ($image_style_mapping['image_mapping_type']) {
        case 'sizes':
          // The image style mapping must have a sizes attribute defined and one
          // or more image styles selected.
          if ($image_style_mapping['image_mapping']['sizes'] && $image_style_mapping['image_mapping']['sizes_image_styles']) {
            return FALSE;
          }
          break;
        case 'image_style':
          // The image style mapping must have an image style selected.
          if ($image_style_mapping['image_mapping']) {
            return FALSE;
          }
          break;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyleMapping($breakpoint_id, $multiplier) {
    $map = $this->getKeyedImageStyleMappings();
    if (isset($map[$breakpoint_id][$multiplier])) {
      return $map[$breakpoint_id][$multiplier];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyleIds() {
    $image_styles = [];
    foreach ($this->getImageStyleMappings() as $image_style_mapping) {
      // Only image styles of non-empty mappings should be loaded.
      if (!$this::isEmptyImageStyleMapping($image_style_mapping)) {
        switch ($image_style_mapping['image_mapping_type']) {
          case 'image_style':
            $image_styles[] = $image_style_mapping['image_mapping'];
            break;
          case 'sizes':
            $image_styles = array_merge($image_styles, $image_style_mapping['image_mapping']['sizes_image_styles']);
            break;
        }
      }
    }
    return array_values(array_filter(array_unique($image_styles)));
  }

}
