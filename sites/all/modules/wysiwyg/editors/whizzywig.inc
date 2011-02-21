<?php
// $Id: whizzywig.inc,v 1.8 2010/11/13 18:31:21 sun Exp $

/**
 * @file
 * Editor integration functions for Whizzywig.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_whizzywig_editor() {
  $editor['whizzywig'] = array(
    'title' => 'Whizzywig',
    'vendor url' => 'http://www.unverse.net',
    'download url' => 'http://www.unverse.net/whizzywig-download.html',
    'libraries' => array(
      '' => array(
        'title' => 'Default',
        'files' => array('whizzywig.js', 'xhtml.js'),
      ),
    ),
    'version callback' => 'wysiwyg_whizzywig_version',
    'settings callback' => 'wysiwyg_whizzywig_settings',
    'plugin callback' => 'wysiwyg_whizzywig_plugins',
    'versions' => array(
      '55' => array(
        'js files' => array('whizzywig.js'),
      ),
      '56' => array(
        'js files' => array('whizzywig-56.js'),
      ),
      '60' => array(
        'js files' => array('whizzywig-60.js'),
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
function wysiwyg_whizzywig_version($editor) {
  $script = $editor['library path'] . '/whizzywig.js';
  if (!file_exists($script)) {
    return;
  }
  $script = fopen($script, 'r');
  $line = fgets($script, 43);
  // 55: Whizzywig v55i
  // 60: Whizzywig 60
  if (preg_match('@Whizzywig v?([0-9]+)@', $line, $version)) {
    fclose($script);
    return $version[1];
  }
  fclose($script);
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
function wysiwyg_whizzywig_settings($editor, $config, $theme) {
  $settings = array();

  // Add path to button images, if available.
  if (is_dir($editor['library path'] . '/btn')) {
    $settings['buttonPath'] = base_path() . $editor['library path'] . '/btn/';
  }
  if (file_exists($editor['library path'] . '/WhizzywigToolbar.png')) {
    $settings['toolbarImagePath'] = base_path() . $editor['library path'] . '/WhizzywigToolbar.png';
  }
  // Filename changed in version 60.
  elseif (file_exists($editor['library path'] . '/icons.png')) {
    $settings['toolbarImagePath'] = base_path() . $editor['library path'] . '/icons.png';
  }

  // Add configured buttons or all available.
  $settings['buttons'] = array();
  if (!empty($config['buttons'])) {
    $buttons = array();
    foreach ($config['buttons'] as $plugin) {
      $buttons = array_merge($buttons, $plugin);
    }
    $settings['buttons'] = implode(' ', array_keys($buttons));
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
function wysiwyg_whizzywig_plugins($editor) {
  return array(
    'default' => array(
      'buttons' => array(
        'formatblock' => t('HTML block format'), 'fontname' => t('Font'), 'fontsize' => t('Font size'),
        'bold' => t('Bold'), 'italic' => t('Italic'), 'underline' => t('Underline'),
        'left' => t('Align left'), 'center' => t('Align center'), 'right' => t('Align right'),
        'bullet' => t('Bullet list'), 'number' => t('Numbered list'),
        'outdent' => t('Outdent'), 'indent' => t('Indent'),
        'undo' => t('Undo'), 'redo' => t('Redo'),
        'image' => t('Image'),
        'color' => t('Forecolor'), 'hilite' => t('Backcolor'),
        'rule' => t('Horizontal rule'),
        'link' => t('Link'),
        'image' => t('Image'),
        'table' => t('Table'),
        'clean' => t('Clean-up'),
        'html' => t('Source code'),
        'spellcheck' => t('Spell check'),
      ),
      'internal' => TRUE,
    ),
  );
}

