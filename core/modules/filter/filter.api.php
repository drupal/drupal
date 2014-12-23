<?php

/**
 * @file
 * Hooks provided by the Filter module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on filter definitions.
 *
 * @param $info
 *   Array of information on filters exposed by filter plugins.
 */
function hook_filter_info_alter(&$info) {
  // Alter the default settings of the URL filter provided by core.
  $info['filter_url']['default_settings'] = array(
    'filter_url_length' => 100,
  );
}

/**
 * Alters images with an invalid source.
 *
 * When the 'Restrict images to this site' filter is enabled, any images that
 * are not hosted on the site will be passed through this hook, most commonly to
 * replace the invalid image with an error indicator.
 *
 * @param DOMElement $image
 *   An IMG node to format, parsed from the filtered text.
 */
function hook_filter_secure_image_alter(&$image) {
  // Turn an invalid image into an error indicator.
  $image->setAttribute('src', base_path() . 'core/misc/icons/ea2800/error.svg');
  $image->setAttribute('alt', t('Image removed.'));
  $image->setAttribute('title', t('This image has been removed. For security reasons, only images from the local domain are allowed.'));

  // Add a CSS class to aid in styling.
  $class = ($image->getAttribute('class') ? trim($image->getAttribute('class')) . ' ' : '');
  $class .= 'filter-image-invalid';
  $image->setAttribute('class', $class);
}

/**
 * Perform actions when a text format has been disabled.
 *
 * @param $format
 *   The format object of the format being disabled.
 */
function hook_filter_format_disable($format) {
  mymodule_cache_rebuild();
}

/**
 * @} End of "addtogroup hooks".
 */
