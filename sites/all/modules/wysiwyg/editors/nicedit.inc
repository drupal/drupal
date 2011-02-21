<?php
// $Id: nicedit.inc,v 1.8 2010/11/13 18:31:20 sun Exp $

/**
 * @file
 * Editor integration functions for NicEdit.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_nicedit_editor() {
  $editor['nicedit'] = array(
    'title' => 'NicEdit',
    'vendor url' => 'http://nicedit.com',
    'download url' => 'http://nicedit.com/download.php',
    'libraries' => array(
      '' => array(
        'title' => 'Source',
        'files' => array('nicEdit.js'),
      ),
    ),
    'version callback' => 'wysiwyg_nicedit_version',
    'settings callback' => 'wysiwyg_nicedit_settings',
    'plugin callback' => 'wysiwyg_nicedit_plugins',
    'versions' => array(
      '0.9' => array(
        'js files' => array('nicedit.js'),
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
function wysiwyg_nicedit_version($editor) {
  // @see http://nicedit.com/forums/viewtopic.php?t=425
  return '0.9';
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
function wysiwyg_nicedit_settings($editor, $config, $theme) {
  $settings = array(
    'iconsPath' => base_path() . $editor['library path'] . '/nicEditorIcons.gif',
  );

  // Add configured buttons or all available.
  $settings['buttonList'] = array();
  if (!empty($config['buttons'])) {
    $buttons = array();
    foreach ($config['buttons'] as $plugin) {
      $buttons = array_merge($buttons, $plugin);
    }
    $settings['buttonList'] = array_keys($buttons);
  }

  // Add editor content stylesheet.
  if (isset($config['css_setting'])) {
    if ($config['css_setting'] == 'theme') {
      $css = path_to_theme() . '/style.css';
      if (file_exists($css)) {
        $settings['externalCSS'] = base_path() . $css;
      }
    }
    else if ($config['css_setting'] == 'self' && isset($config['css_path'])) {
      $settings['externalCSS'] = strtr($config['css_path'], array('%b' => base_path(), '%t' => path_to_theme()));
    }
  }

  return $settings;
}

/**
 * Return internal plugins for this editor; semi-implementation of hook_wysiwyg_plugin().
 */
function wysiwyg_nicedit_plugins($editor) {
  return array(
    'default' => array(
      'buttons' => array(
        'bold' => t('Bold'), 'italic' => t('Italic'), 'underline' => t('Underline'),
        'strikethrough' => t('Strike-through'),
        'left' => t('Align left'), 'center' => t('Align center'), 'right' => t('Align right'),
        'ul' => t('Bullet list'), 'ol' => t('Numbered list'),
        'outdent' => t('Outdent'), 'indent' => t('Indent'),
        'image' => t('Image'),
        'forecolor' => t('Forecolor'), 'bgcolor' => t('Backcolor'),
        'superscript' => t('Superscript'), 'subscript' => t('Subscript'),
        'hr' => t('Horizontal rule'),
        // @todo New challenge: Optional internal plugins packaged into editor
        //   library.
        'link' => t('Link'), 'unlink' => t('Unlink'),
        'fontFormat' => t('HTML block format'), 'fontFamily' => t('Font'), 'fontSize' => t('Font size'),
        'xhtml' => t('Source code'),
      ),
      'internal' => TRUE,
    ),
  );
}

