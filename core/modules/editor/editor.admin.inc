<?php

/**
 * @file
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. See the sole
 *   function for the deprecation notice with upgrade instructions.
 *
 * @see https://www.drupal.org/node/3570919
 */

@trigger_error(__FILE__ . ' is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. See the sole function for the deprecation notice with upgrade instructions. See https://www.drupal.org/node/3570919', E_USER_DEPRECATED);

use Drupal\editor\Entity\Editor;
use Drupal\editor\EditorImageUploadSettings;

/**
 * Subform constructor to configure the text editor's image upload settings.
 *
 * Each text editor plugin that is configured to offer the ability to insert
 * images, should use this form to update the text editor's configuration so
 * that it knows whether it should allow the user to upload images.
 *
 * @param \Drupal\editor\Entity\Editor $editor
 *   The text editor entity that is being edited.
 *
 * @return array
 *   The image upload settings form.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\editor\EditorImageUploadSettings::getForm() instead.
 *
 * @see https://www.drupal.org/node/3570919
 */
function editor_image_upload_settings_form(Editor $editor) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal\editor\EditorImageUploadSettings::getForm() instead. See https://www.drupal.org/node/3570919', E_USER_DEPRECATED);
  return \Drupal::service(EditorImageUploadSettings::class)->getForm($editor);
}
