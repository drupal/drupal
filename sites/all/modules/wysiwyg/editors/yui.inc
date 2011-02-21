<?php
// $Id: yui.inc,v 1.11 2010/11/13 18:31:21 sun Exp $

/**
 * @file
 * Editor integration functions for YUI editor.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_yui_editor() {
  $editor['yui'] = array(
    'title' => 'YUI editor',
    'vendor url' => 'http://developer.yahoo.com/yui/editor/',
    'download url' => 'http://developer.yahoo.com/yui/download/',
    'library path' => wysiwyg_get_path('yui') . '/build',
    'libraries' => array(
      'min' => array(
        'title' => 'Minified',
        'files' => array(
          'yahoo-dom-event/yahoo-dom-event.js',
          'animation/animation-min.js',
          'element/element-min.js',
          'container/container-min.js',
          'menu/menu-min.js',
          'button/button-min.js',
          'editor/editor-min.js',
        ),
      ),
      'src' => array(
        'title' => 'Source',
        'files' => array(
          'yahoo-dom-event/yahoo-dom-event.js',
          'animation/animation.js',
          'element/element.js',
          'container/container.js',
          'menu/menu.js',
          'button/button.js',
          'editor/editor.js',
        ),
      ),
    ),
    'version callback' => 'wysiwyg_yui_version',
    'themes callback' => 'wysiwyg_yui_themes',
    'load callback' => 'wysiwyg_yui_load',
    'settings callback' => 'wysiwyg_yui_settings',
    'plugin callback' => 'wysiwyg_yui_plugins',
    'versions' => array(
      '2.7.0' => array(
        'js files' => array('yui.js'),
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
function wysiwyg_yui_version($editor) {
  $library = $editor['library path'] . '/editor/editor.js';
  if (!file_exists($library)) {
    return;
  }
  $library = fopen($library, 'r');
  $max_lines = 10;
  while ($max_lines && $line = fgets($library, 60)) {
    if (preg_match('@version:\s([0-9\.]+)@', $line, $version)) {
      fclose($library);
      return $version[1];
    }
    $max_lines--;
  }
  fclose($library);
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
function wysiwyg_yui_themes($editor, $profile) {
  return array('sam');
}

/**
 * Perform additional actions upon loading this editor.
 *
 * @param $editor
 *   A processed hook_editor() array of editor properties.
 * @param $library
 *   The internal library name (array key) to use.
 */
function wysiwyg_yui_load($editor, $library) {
  drupal_add_css($editor['library path'] . '/menu/assets/skins/sam/menu.css');
  drupal_add_css($editor['library path'] . '/button/assets/skins/sam/button.css');
  drupal_add_css($editor['library path'] . '/fonts/fonts-min.css');
  drupal_add_css($editor['library path'] . '/container/assets/skins/sam/container.css');
  drupal_add_css($editor['library path'] . '/editor/assets/skins/sam/editor.css');
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
function wysiwyg_yui_settings($editor, $config, $theme) {
  $settings = array(
    'theme' => $theme,
    'animate' => TRUE,
    'handleSubmit' => TRUE,
    'markup' => 'xhtml',
    'ptags' => TRUE,
  );

  if (isset($config['path_loc']) && $config['path_loc'] != 'none') {
    $settings['dompath'] = $config['path_loc'];
  }
  // Enable auto-height feature when editor should be resizable.
  if (!empty($config['resizing'])) {
    $settings['autoHeight'] = TRUE;
  }

  $settings += array(
    'toolbar' => array(
      'collapse' => FALSE,
      'draggable' => TRUE,
      'buttonType' => 'advanced',
      'buttons' => array(),
    ),
  );
  if (!empty($config['buttons'])) {
    $buttons = array();
    foreach ($config['buttons'] as $plugin => $enabled_buttons) {
      foreach ($enabled_buttons as $button => $enabled) {
        $extra = array();
        if ($button == 'heading') {
          $extra = array('menu' => array(
            array('text' => 'Normal', 'value' => 'none', 'checked' => TRUE),
          ));
          if (!empty($config['block_formats'])) {
            $headings = array(
              'p' => array('text' => 'Paragraph', 'value' => 'p'),
              'h1' => array('text' => 'Heading 1', 'value' => 'h1'),
              'h2' => array('text' => 'Heading 2', 'value' => 'h2'),
              'h3' => array('text' => 'Heading 3', 'value' => 'h3'),
              'h4' => array('text' => 'Heading 4', 'value' => 'h4'),
              'h5' => array('text' => 'Heading 5', 'value' => 'h5'),
              'h6' => array('text' => 'Heading 6', 'value' => 'h6'),
            );
            foreach (explode(',', $config['block_formats']) as $tag) {
              if (isset($headings[$tag])) {
                $extra['menu'][] = $headings[$tag];
              }
            }
          }
        }
        else if ($button == 'fontname') {
          $extra = array('menu' => array(
            array('text' => 'Arial', 'checked' => TRUE),
            array('text' => 'Arial Black'),
            array('text' => 'Comic Sans MS'),
            array('text' => 'Courier New'),
            array('text' => 'Lucida Console'),
            array('text' => 'Tahoma'),
            array('text' => 'Times New Roman'),
            array('text' => 'Trebuchet MS'),
            array('text' => 'Verdana'),
          ));
        }
        $buttons[] = wysiwyg_yui_button_setting($editor, $plugin, $button, $extra);
      }
    }
    // Group buttons in a dummy group.
    $buttons = array('group' => 'default', 'label' => '', 'buttons' => $buttons);
    $settings['toolbar']['buttons'] = array($buttons);
  }

  if (isset($config['css_setting'])) {
    if ($config['css_setting'] == 'theme') {
      $settings['extracss'] = wysiwyg_get_css();
    }
    else if ($config['css_setting'] == 'self' && isset($config['css_path'])) {
      $settings['extracss'] = strtr($config['css_path'], array('%b' => base_path(), '%t' => path_to_theme()));
      $settings['extracss'] = explode(',', $settings['extracss']);
    }
    // YUI only supports inline CSS, so we need to use @import directives.
    // Syntax: '@import "/base/path/to/theme/style.css"; '
    if (!empty($settings['extracss'])) {
      $settings['extracss'] = '@import "' . implode('"; @import "', $settings['extracss']) . '";';
    }
  }

  return $settings;
}

/**
 * Create the JavaScript structure for a YUI button.
 *
 * @param $editor
 *   A processed hook_editor() array of editor properties.
 * @param $plugin
 *   The internal name of a plugin.
 * @param $button
 *   The internal name of a button, defined by $plugin.
 * @param $extra
 *   (optional) An array containing arbitrary other elements to add to the
 *   resulting button.
 */
function wysiwyg_yui_button_setting($editor, $plugin, $button, $extra = array()) {
  static $plugins;

  if (!isset($plugins)) {
    // @todo Invoke all enabled plugins, not just internals.
    $plugins = wysiwyg_yui_plugins($editor);
  }

  // Return a simple separator.
  if ($button === 'separator') {
    return array('type' => 'separator');
  }
  // Setup defaults.
  $type = 'push';
  $label = $plugins[$plugin]['buttons'][$button];

  // Special handling for certain buttons.
  if (in_array($button, array('heading', 'fontname'))) {
    $type = 'select';
    $label = $extra['menu'][0]['text'];
  }
  elseif (in_array($button, array('fontsize'))) {
    $type = 'spin';
  }
  elseif (in_array($button, array('forecolor', 'backcolor'))) {
    $type = 'color';
  }

  $button = array(
    'type' => $type,
    'label' => $label,
    'value' => $button,
  );
  // Add arbitrary other elements, if defined.
  if (!empty($extra)) {
    $button = array_merge($button, $extra);
  }
  return $button;
}

/**
 * Return internal plugins for this editor; semi-implementation of hook_wysiwyg_plugin().
 */
function wysiwyg_yui_plugins($editor) {
  return array(
    'default' => array(
      'buttons' => array(
        'bold' => t('Bold'), 'italic' => t('Italic'), 'underline' => t('Underline'),
        'strikethrough' => t('Strike-through'),
        'justifyleft' => t('Align left'), 'justifycenter' => t('Align center'), 'justifyright' => t('Align right'), 'justifyfull' => t('Justify'),
        'insertunorderedlist' => t('Bullet list'), 'insertorderedlist' => t('Numbered list'),
        'outdent' => t('Outdent'), 'indent' => t('Indent'),
        'undo' => t('Undo'), 'redo' => t('Redo'),
        'createlink' => t('Link'),
        'insertimage' => t('Image'),
        'forecolor' => t('Font Color'), 'backcolor' => t('Background Color'),
        'superscript' => t('Sup'), 'subscript' => t('Sub'),
        'hiddenelements' => t('Show/hide hidden elements'),
        'removeformat' => t('Remove format'),
        'heading' => t('HTML block format'), 'fontname' => t('Font'), 'fontsize' => t('Font size'),
      ),
      'internal' => TRUE,
    ),
  );
}

