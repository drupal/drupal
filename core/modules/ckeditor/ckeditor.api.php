<?php

/**
 * @file
 * Documentation for CKEditor module APIs.
 */

use Drupal\editor\Entity\Editor;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the list of available CKEditor plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see CKEditorPluginManager
 */
function hook_ckeditor_plugin_info_alter(array &$plugins) {
  $plugins['someplugin']['label'] = t('Better name');
}

/**
 * Modify the list of CSS files that will be added to a CKEditor instance.
 *
 * Modules may use this hook to provide their own custom CSS file without
 * providing a CKEditor plugin. This list of CSS files is only used in the
 * iframe versions of CKEditor.
 *
 * Front-end themes (and base themes) can easily specify CSS files to be used in
 * iframe instances of CKEditor through an entry in their .info file:
 *
 * @code
 * ckeditor_stylesheets[] = css/ckeditor-iframe.css
 * @endcode
 *
 * @param array &$css
 *   An array of CSS files, passed by reference. This is a flat list of file
 *   paths relative to the Drupal root.
 * @param $editor
 *   The text editor object as returned by editor_load(), for which these files
 *   are being loaded. Based on this information, it is possible to load the
 *   corresponding text format object.
 *
 * @see _ckeditor_theme_css()
 */
function hook_ckeditor_css_alter(array &$css, Editor $editor) {
  $css[] = drupal_get_path('module', 'mymodule') . '/css/mymodule-ckeditor.css';
}

/**
 * @} End of "addtogroup hooks".
 */
