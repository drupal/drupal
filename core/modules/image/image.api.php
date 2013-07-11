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
 * Define information about image effects provided by a module.
 *
 * This hook enables modules to define image manipulation effects for use with
 * an image style.
 *
 * @return
 *   An array of image effects. This array is keyed on the machine-readable
 *   effect name. Each effect is defined as an associative array containing the
 *   following items:
 *   - "label": The human-readable name of the effect.
 *   - "effect callback": The function to call to perform this image effect.
 *   - "dimensions passthrough": (optional) Set this item if the effect doesn't
 *     change the dimensions of the image.
 *   - "dimensions callback": (optional) The function to call to transform
 *     dimensions for this effect.
 *   - "help": (optional) A brief description of the effect that will be shown
 *     when adding or configuring this image effect.
 *   - "form callback": (optional) The name of a function that will return a
 *     $form array providing a configuration form for this image effect.
 *   - "summary theme": (optional) The name of a theme function that will output
 *     a summary of this image effect's configuration.
 *
 * @see hook_image_effect_info_alter()
 */
function hook_image_effect_info() {
  $effects = array();

  $effects['mymodule_resize'] = array(
    'label' => t('Resize'),
    'help' => t('Resize an image to an exact set of dimensions, ignoring aspect ratio.'),
    'effect callback' => 'mymodule_resize_effect',
    'dimensions callback' => 'mymodule_resize_dimensions',
    'form callback' => 'mymodule_resize_form',
    'summary theme' => 'mymodule_resize_summary',
  );

  return $effects;
}

/**
 * Alter the information provided in hook_image_effect_info().
 *
 * @param $effects
 *   The array of image effects, keyed on the machine-readable effect name.
 *
 * @see hook_image_effect_info()
 */
function hook_image_effect_info_alter(&$effects) {
  // Override the Image module's crop effect with more options.
  $effects['image_crop']['effect callback'] = 'mymodule_crop_effect';
  $effects['image_crop']['dimensions callback'] = 'mymodule_crop_dimensions';
  $effects['image_crop']['form callback'] = 'mymodule_crop_form';
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
  cache('mymodule')->deleteAll();
}

 /**
  * @} End of "addtogroup hooks".
  */
