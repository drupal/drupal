<?php
// $Id: openwysiwyg.inc,v 1.7 2010/11/13 18:31:21 sun Exp $

/**
 * @file
 * Editor integration functions for openWYSIWYG.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_openwysiwyg_editor() {
  $editor['openwysiwyg'] = array(
    'title' => 'openWYSIWYG',
    'vendor url' => 'http://www.openwebware.com',
    'download url' => 'http://www.openwebware.com/download.shtml',
    'library path' => wysiwyg_get_path('openwysiwyg') . '/scripts',
    'libraries' => array(
      'src' => array(
        'title' => 'Source',
        'files' => array('wysiwyg.js'),
      ),
    ),
    'version callback' => 'wysiwyg_openwysiwyg_version',
    'themes callback' => 'wysiwyg_openwysiwyg_themes',
    'settings callback' => 'wysiwyg_openwysiwyg_settings',
    'plugin callback' => 'wysiwyg_openwysiwyg_plugins',
    'versions' => array(
      '1.4.7' => array(
        'js files' => array('openwysiwyg.js'),
        'css files' => array('openwysiwyg.css'),
      ),
    ),
  );
  return $editor;
}

/**
 * Detect editor version.
 *
 * @param $editor
 *   An array containing editor properties as returned from hook_editor().
 *
 * @return
 *   The installed editor version.
 */
function wysiwyg_openwysiwyg_version($editor) {
  // 'library path' has '/scripts' appended already.
  $changelog = $editor['editor path'] . '/changelog';
  if (!file_exists($changelog)) {
    return;
  }
  $changelog = fopen($changelog, 'r');
  $line = fgets($changelog, 20);
  if (preg_match('@v([\d\.]+)@', $line, $version)) {
    fclose($changelog);
    return $version[1];
  }
  fclose($changelog);
}

/**
 * Determine available editor themes or check/reset a given one.
 *
 * @param $editor
 *   A processed hook_editor() array of editor properties.
 * @param $profile
 *   A wysiwyg editor profile.
 *
 * @return
 *   An array of theme names. The first returned name should be the default
 *   theme name.
 */
function wysiwyg_openwysiwyg_themes($editor, $profile) {
  return array('default');
}

/**
 * Return runtime editor settings for a given wysiwyg profile.
 *
 * @param $editor
 *   A processed hook_editor() array of editor properties.
 * @param $config
 *   An array containing wysiwyg editor profile settings.
 * @param $theme
 *   The name of a theme/GUI/skin to use.
 *
 * @return
 *   A settings array to be populated in
 *   Drupal.settings.wysiwyg.configs.{editor}
 */
function wysiwyg_openwysiwyg_settings($editor, $config, $theme) {
  $settings = array(
    'path' => base_path() . $editor['editor path'] . '/',
    'Width' => '100%',
  );

  if (isset($config['path_loc']) && $config['path_loc'] == 'none') {
    $settings['StatusBarEnabled'] = FALSE;
  }

  if (isset($config['css_setting'])) {
    if ($config['css_setting'] == 'theme') {
      $settings['CSSFile'] = reset(wysiwyg_get_css());
    }
    else if ($config['css_setting'] == 'self' && isset($config['css_path'])) {
      $settings['CSSFile'] = strtr($config['css_path'], array('%b' => base_path(), '%t' => path_to_theme()));
    }
  }

  $settings['Toolbar'] = array();
  if (!empty($config['buttons'])) {
    $plugins = wysiwyg_get_plugins($editor['name']);
    foreach ($config['buttons'] as $plugin => $buttons) {
      foreach ($buttons as $button => $enabled) {
        foreach (array('buttons', 'extensions') as $type) {
          // Skip unavailable plugins.
          if (!isset($plugins[$plugin][$type][$button])) {
            continue;
          }
          // Add buttons.
          if ($type == 'buttons') {
            $settings['Toolbar'][0][] = $button;
          }
        }
      }
    }
  }

  // @todo 
//  if (isset($config['block_formats'])) {
//    $settings['DropDowns']['headings']['elements'] = explode(',', $config['block_formats']);
//  }

  return $settings;
}

/**
 * Return internal plugins for this editor; semi-implementation of hook_wysiwyg_plugin().
 */
function wysiwyg_openwysiwyg_plugins($editor) {
  $plugins = array(
    'default' => array(
      'buttons' => array(
        'bold' => t('Bold'), 'italic' => t('Italic'), 'underline' => t('Underline'),
        'strikethrough' => t('Strike-through'),
        'justifyleft' => t('Align left'), 'justifycenter' => t('Align center'), 'justifyright' => t('Align right'), 'justifyfull' => t('Justify'),
        'unorderedlist' => t('Bullet list'), 'orderedlist' => t('Numbered list'),
        'outdent' => t('Outdent'), 'indent' => t('Indent'),
        'undo' => t('Undo'), 'redo' => t('Redo'),
        'createlink' => t('Link'),
        'insertimage' => t('Image'),
        'cleanup' => t('Clean-up'),
        'forecolor' => t('Forecolor'), 'backcolor' => t('Backcolor'),
        'superscript' => t('Sup'), 'subscript' => t('Sub'),
        'blockquote' => t('Blockquote'), 'viewSource' => t('Source code'),
        'hr' => t('Horizontal rule'),
        'cut' => t('Cut'), 'copy' => t('Copy'), 'paste' => t('Paste'),
        'visualaid' => t('Visual aid'),
        'removeformat' => t('Remove format'),
        'charmap' => t('Character map'),
        'headings' => t('HTML block format'), 'font' => t('Font'), 'fontsize' => t('Font size'),
        'maximize' => t('Fullscreen'),
        'preview' => t('Preview'),
        'print' => t('Print'),
        'inserttable' => t('Table'),
        'help' => t('Help'),
      ),
      'internal' => TRUE,
    ),
  );
  return $plugins;
}

