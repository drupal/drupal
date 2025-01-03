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
 * @param array $effects
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
 * @param string|null $path
 *   (optional) The original image path or URI. If it's supplied, only this
 *   image derivative will be flushed.
 */
function hook_image_style_flush($style, $path = NULL): void {
  // Empty cached data that contains information about the style.
  \Drupal::cache('my_module')->deleteAll();
}

/**
 * @} End of "addtogroup hooks".
 */
