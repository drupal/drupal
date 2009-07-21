<?php
// $Id$

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
 *   - "help": (optional) A brief description of the effect that will be shown
 *     when adding or configuring this image effect.
 *   - "form callback": (optional) The name of a function that will return a
 *     $form array providing a configuration form for this image effect.
 *   - "summary theme": (optional) The name of a theme function that will output
 *     a summary of this image effect's configuration.
 */
function hook_image_effect_info() {
  $effects = array();

  $effects['mymodule_resize'] = array(
    'label' => t('Resize'),
    'help' => t('Resize an image to an exact set of dimensions, ignoring aspect ratio.'),
    'effect callback' => 'mymodule_resize_image',
    'form callback' => 'mymodule_resize_form',
    'summary theme' => 'mymodule_resize_summary',
  );

  return $effects;
}

/**
 * Respond to image style updating.
 *
 * This hook enables modules to update settings that might be affected by
 * changes to an image. For example, updating a module specific variable to
 * reflect a change in the image style's name.
 *
 * @param $style
 *   The image style array that is being updated.
 */
function hook_image_style_save($style) {
  // If a module defines an image style and that style is renamed by the user
  // the module should update any references to that style.
  if (isset($style['old_name']) && $style['old_name'] == variable_get('mymodule_image_style', '')) {
    variable_set('mymodule_image_style', $style['name']);
  }
}

/**
 * Respond to image style deletion.
 *
 * This hook enables modules to update settings when a image style is being
 * deleted. If a style is deleted, a replacement name may be specified in
 * $style['name'] and the style being deleted will be specified in
 * $style['old_name'].
 *
 * @param $style
 *   The image style array that being deleted.
 */
function hook_image_style_delete($style) {
  // Administrators can choose an optional replacement style when deleting.
  // Update the modules style variable accordingly.
  if (isset($style['old_name']) && $style['old_name'] == variable_get('mymodule_image_style', '')) {
    variable_set('mymodule_image_style', $style['name']);
  }
}

/**
 * Respond to image style flushing.
 *
 * This hook enables modules to take effect when a style is being flushed (all
 * images are being deleted from the server and regenerated). Any
 * module-specific caches that contain information related to the style should
 * be cleared using this hook. This hook is called whenever a style is updated,
 * deleted, any effect associated with the style is update or deleted, or when
 * the user selects the style flush option.
 *
 * @param $style
 *   The image style array that is being flushed.
 */
function hook_image_style_flush($style) {
  // Empty cached data that contains information about the style.
  cache_clear_all('*', 'cache_mymodule', TRUE);
}
 /**
  * @} End of "addtogroup hooks".
  */
