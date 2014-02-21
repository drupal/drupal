<?php

/**
 * @file
 * Hooks related to image styles and effects.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\image\Annotation\ImageEffect.
 *
 * @param $effects
 *   The array of image effects, keyed on the machine-readable effect name.
 */
function hook_image_effect_info_alter(&$effects) {
  // Override the Image module's 'Scale and Crop' effect label.
  $effects['image_scale_and_crop']['label'] = t('Bangers and Mash');
}

/**
 * Respond to image style flushing.
 *
 * This hook enables modules to take effect when a style is being flushed (all
 * images are being deleted from the server and regenerated). Any
 * module-specific caches that contain information related to the style should
 * be cleared using this hook. This hook is called whenever a style is updated,
 * deleted, or any effect associated with the style is update or deleted.
 *
 * @param \Drupal\image\ImageStyleInterface $style
 *   The image style object that is being flushed.
 */
function hook_image_style_flush($style) {
  // Empty cached data that contains information about the style.
  \Drupal::cache('mymodule')->deleteAll();
}

 /**
  * @} End of "addtogroup hooks".
  */
