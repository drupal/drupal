<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleStorageController.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityInterface;
use Drupal\image\Plugin\Core\Entity\ImageStyle;

/**
 * Defines a controller class for image styles.
 */
class ImageStyleStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    foreach ($queried_entities as $style) {
      if (!empty($style->effects)) {
        foreach ($style->effects as $ieid => $effect) {
          $definition = image_effect_definition_load($effect['name']);
          $effect = array_merge($definition, $effect);
          $style->effects[$ieid] = $effect;
        }
        // Sort effects by weight.
        uasort($style->effects, 'drupal_sort_weight');
      }
    }
    parent::attachLoad($queried_entities, $revision_id);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    if ($update && !empty($entity->original) && $entity->{$this->idKey} !== $entity->original->{$this->idKey}) {
      // The old image style name needs flushing after a rename.
      image_style_flush($entity->original);
      // Update field instance settings if necessary.
      $this->replaceImageStyle($entity);
    }
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::postDelete().
   */
  protected function postDelete($entities) {
    foreach ($entities as $style) {
      // Flush cached media for the deleted style.
      image_style_flush($style);
      // Check whether field instance settings need to be updated.
      // In case no replacement style was specified, all image fields that are
      // using the deleted style are left in a broken state.
      if ($new_id = $style->get('replacementID')) {
        // The deleted ID is still set as originalID.
        $style->set('name', $new_id);
        $this->replaceImageStyle($style);
      }
    }
  }

  /**
   * Update field instance settings if the image style name is changed.
   *
   * @param ImageStyle $style
   *   The image style.
   */
  protected function replaceImageStyle(ImageStyle $style) {
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
