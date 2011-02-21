<?php
// $Id: markitup.inc,v 1.12 2010/12/19 19:19:56 sun Exp $

/**
 * @file
 * Editor integration functions for markItUp.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_markitup_editor() {
  $editor['markitup'] = array(
    'title' => 'markItUp',
    'vendor url' => 'http://markitup.jaysalvat.com',
    'download url' => 'http://markitup.jaysalvat.com/downloads',
    'library path' => wysiwyg_get_path('markitup'),
    'libraries' => array(
      '' => array(
        'title' => 'Source',
        'files' => array('markitup/jquery.markitup.js'),
      ),
      'pack' => array(
        'title' => 'Packed',
        'files' => array('markitup/jquery.markitup.pack.js'),
      ),
    ),
    'version callback' => 'wysiwyg_markitup_version',
    'themes callback' => 'wysiwyg_markitup_themes',
    'settings callback' => 'wysiwyg_markitup_settings',
    'plugin callback' => 'wysiwyg_markitup_plugins',
    'versions' => array(
      '1.1.5' => array(
        'js files' => array('markitup.js'),
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
function wysiwyg_markitup_version($editor) {
  // Changelog was in markitup/markitup/readme.txt <= 1.1.5.
  $changelog = $editor['library path'] . '/markitup/readme.txt';
  if (!file_exists($changelog)) {
    // Changelog was moved up to markitup/CHANGELOG.md after 1.1.5.
    $changelog = $editor['library path'] . '/CHANGELOG.md';
    if (!file_exists($changelog)) {
      return;
    }
  }
  $changelog = fopen($changelog, 'r');
  $line = fgets($changelog);
  if (preg_match('@([0-9\.]+)@', $line, $version)) {
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
function wysiwyg_markitup_themes($editor, $profile) {
  return array('simple', 'markitup');
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
function wysiwyg_markitup_settings($editor, $config, $theme) {
  drupal_add_css($editor['library path'] . '/markitup/skins/' . $theme . '/style.css', array(
    // Specify an alternate basename; otherwise, style.css would override a
    // commonly used style.css file of the theme.
    'basename' => 'markitup.' . $theme . '.style.css',
    'group' => CSS_THEME,
  ));

  $settings = array(
    'root' => base_path() . $editor['library path'] . '/markitup/',
    'nameSpace' => $theme,
    'markupSet' => array(),
  );

  // Add configured buttons or all available.
  $default_buttons = array(
    'bold' => array(
      'name' => t('Bold'),
      'className' => 'markitup-bold',
      'key' => 'B',
      'openWith' => '(!(<strong>|!|<b>)!)',
      'closeWith' => '(!(</strong>|!|</b>)!)',
    ),
    'italic' => array(
      'name' => t('Italic'),
      'className' => 'markitup-italic',
      'key' => 'I',
      'openWith' => '(!(<em>|!|<i>)!)',
      'closeWith' => '(!(</em>|!|</i>)!)',
    ),
    'stroke' => array(
      'name' => t('Strike-through'),
      'className' => 'markitup-stroke',
      'key' => 'S',
      'openWith' => '<del>',
      'closeWith' => '</del>',
    ),
    'image' => array(
      'name' => t('Image'),
      'className' => 'markitup-image',
      'key' => 'P',
      'replaceWith' => '<img src="[![Source:!:http://]!]" alt="[![Alternative text]!]" />',
    ),
    'link' => array(
      'name' => t('Link'),
      'className' => 'markitup-link',
      'key' => 'K',
      'openWith' => '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',
      'closeWith' => '</a>',
      'placeHolder' => 'Your text to link...',
    ),
    // @todo
    // 'cleanup' => array('name' => t('Clean-up'), 'className' => 'markitup-cleanup', 'replaceWith' => 'function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") }'),
    'preview' => array(
      'name' => t('Preview'),
      'className' => 'markitup-preview',
      'call' => 'preview',
    ),
  );
  $settings['markupSet'] = array();
  if (!empty($config['buttons'])) {
    foreach ($config['buttons'] as $plugin) {
      foreach ($plugin as $button => $enabled) {
        if (isset($default_buttons[$button])) {
          $settings['markupSet'][$button] = $default_buttons[$button];
        }
      }
    }
  }

  return $settings;
}

/**
 * Return internal plugins for this editor; semi-implementation of hook_wysiwyg_plugin().
 */
function wysiwyg_markitup_plugins($editor) {
  return array(
    'default' => array(
      'buttons' => array(
        'bold' => t('Bold'), 'italic' => t('Italic'),
        'stroke' => t('Strike-through'),
        'link' => t('Link'),
        'image' => t('Image'),
        // 'cleanup' => t('Clean-up'),
        'preview' => t('Preview'),
      ),
      'internal' => TRUE,
    ),
  );
}

