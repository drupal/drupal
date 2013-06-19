<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Core\Entity\ImageStyle.
 */

namespace Drupal\image\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\image\ImageStyleInterface;

/**
 * Defines an image style configuration entity.
 *
 * @EntityType(
 *   id = "image_style",
 *   label = @Translation("Image style"),
 *   module = "image",
 *   controllers = {
 *     "form" = {
 *       "delete" = "Drupal\image\Form\ImageStyleDeleteForm"
 *     },
 *     "storage" = "Drupal\image\ImageStyleStorageController"
 *   },
 *   uri_callback = "image_style_entity_uri",
 *   config_prefix = "image.style",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class ImageStyle extends ConfigEntityBase implements ImageStyleInterface {

  /**
   * The name of the image style to use as replacement upon delete.
   *
   * @var string
   */
  protected $replacementID;

  /**
   * The name of the image style.
   *
   * @var string
   */
  public $name;

  /**
   * The image style label.
   *
   * @var string
   */
  public $label;

  /**
   * The array of image effects for this image style.
   *
   * @var string
   */
  public $effects;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    if ($update && !empty($this->original) && $this->id() !== $this->original->id()) {
      // The old image style name needs flushing after a rename.
      image_style_flush($this->original);
      // Update field instance settings if necessary.
      static::replaceImageStyle($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    foreach ($entities as $style) {
      // Flush cached media for the deleted style.
      image_style_flush($style);
      // Check whether field instance settings need to be updated.
      // In case no replacement style was specified, all image fields that are
      // using the deleted style are left in a broken state.
      if ($new_id = $style->get('replacementID')) {
        // The deleted ID is still set as originalID.
        $style->set('name', $new_id);
        static::replaceImageStyle($style);
      }
    }
  }

  /**
   * Update field instance settings if the image style name is changed.
   *
   * @param \Drupal\image\Plugin\Core\Entity\ImageStyle $style
   *   The image style.
   */
  protected static function replaceImageStyle(ImageStyle $style) {
    if ($style->id() != $style->getOriginalID()) {
      $instances = field_read_instances();
      // Loop through all fields searching for image fields.
      foreach ($instances as $instance) {
        if ($instance->getField()->type == 'image') {
          $view_modes = entity_get_view_modes($instance['entity_type']);
          $view_modes = array('default') + array_keys($view_modes);
          foreach ($view_modes as $view_mode) {
            $display = entity_get_display($instance['entity_type'], $instance['bundle'], $view_mode);
            $display_options = $display->getComponent($instance['field_name']);

            // Check if the formatter involves an image style.
            if ($display_options && $display_options['type'] == 'image' && $display_options['settings']['image_style'] == $style->getOriginalID()) {
              // Update display information for any instance using the image
              // style that was just deleted.
              $display_options['settings']['image_style'] = $style->id();
              $display->setComponent($instance['field_name'], $display_options)
                ->save();
            }
          }
          $entity_form_display = entity_get_form_display($instance['entity_type'], $instance['bundle'], 'default');
          $widget_configuration = $entity_form_display->getComponent($instance['field_name']);
          if ($widget_configuration['settings']['preview_image_style'] == $style->getOriginalID()) {
            $widget_options['settings']['preview_image_style'] = $style->id();
            $entity_form_display->setComponent($instance['field_name'], $widget_options)
              ->save();
          }
        }
      }
    }
  }

}
