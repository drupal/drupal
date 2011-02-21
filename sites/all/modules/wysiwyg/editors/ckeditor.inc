<?php
// $Id: ckeditor.inc,v 1.11 2010/12/25 19:12:56 sun Exp $

/**
 * @file
 * Editor integration functions for CKEditor.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_ckeditor_editor() {
  $editor['ckeditor'] = array(
    'title' => 'CKEditor',
    'vendor url' => 'http://ckeditor.com',
    'download url' => 'http://ckeditor.com/download',
    'libraries' => array(
      '' => array(
        'title' => 'Default',
        'files' => array(
          'ckeditor.js' => array('preprocess' => FALSE),
        ),
      ),
      'src' => array(
        'title' => 'Source',
        'files' => array(
          'ckeditor_source.js' => array('preprocess' => FALSE),
        ),
      ),
    ),
    'version callback' => 'wysiwyg_ckeditor_version',
    'themes callback' => 'wysiwyg_ckeditor_themes',
    'settings callback' => 'wysiwyg_ckeditor_settings',
    'plugin callback' => 'wysiwyg_ckeditor_plugins',
    'plugin settings callback' => 'wysiwyg_ckeditor_plugin_settings',
    'proxy plugin' => array(
      'drupal' => array(
        'load' => TRUE,
        'proxy' => TRUE,
      ),
    ),
    'proxy plugin settings callback' => 'wysiwyg_ckeditor_proxy_plugin_settings',
    'versions' => array(
      '3.0.0.3665' => array(
        'js files' => array('ckeditor-3.0.js'),
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
function wysiwyg_ckeditor_version($editor) {
  $library = $editor['library path'] . '/ckeditor.js';
  if (!file_exists($library)) {
    return;
  }
  $library = fopen($library, 'r');
  $max_lines = 8;
  while ($max_lines && $line = fgets($library, 140)) {
    // version:'CKEditor 3.0 SVN',revision:'3665'
    // version:'3.0 RC',revision:'3753'
    // version:'3.0.1',revision:'4391'
    if (preg_match('@version:\'(?:CKEditor )?([\d\.]+)(?:.+revision:\'([\d]+))?@', $line, $version)) {
      fclose($library);
      // Version numbers need to have three parts since 3.0.1.
      $version[1] = preg_replace('/^(\d+)\.(\d+)$/', '${1}.${2}.0', $version[1]);
      return $version[1] . '.' . $version[2];
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
function wysiwyg_ckeditor_themes($editor, $profile) {
  // @todo Skins are not themes but this will do for now.
  $path = $editor['library path'] . '/skins/';
  if (file_exists($path) && ($dir_handle = opendir($path))) {
    $themes = array();
    while ($file = readdir($dir_handle)) {
      if (is_dir($path . $file) && substr($file, 0, 1) != '.' && $file != 'CVS') {
        $themes[] = $file;
      }
    }
    closedir($dir_handle);
    natcasesort($themes);
    return !empty($themes) ? $themes : array('default');
  }
  else {
    return array('default');
  }
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
function wysiwyg_ckeditor_settings($editor, $config, $theme) {
  $settings = array(
    'baseHref' => $GLOBALS['base_url'] . '/',
    'width' => '100%',
    // For better compatibility with smaller textareas.
    'resize_minWidth' => 450,
    'height' => 420,
    // @todo Do not use skins as themes and add separate skin handling.
    'theme' => 'default',
    'skin' => !empty($theme) ? $theme : 'kama',
    // By default, CKEditor converts most characters into HTML entities. Since
    // it does not support a custom definition, but Drupal supports Unicode, we
    // disable at least the additional character sets. CKEditor always converts
    // XML default characters '&', '<', '>'.
    // @todo Check whether completely disabling ProcessHTMLEntities is an option.
    'entities_latin' => FALSE,
    'entities_greek' => FALSE,
  );

  // Add HTML block format settings; common block formats are already predefined
  // by CKEditor.
  if (isset($config['block_formats'])) {
    $block_formats = explode(',', drupal_strtolower($config['block_formats']));
    $predefined_formats = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre', 'address', 'div');
    foreach (array_diff($block_formats, $predefined_formats) as $tag) {
      $tag = trim($tag);
      $settings["format_$tag"] = array('element' => $tag);
    }
    $settings['format_tags'] = implode(';', $block_formats);
  }

  if (isset($config['apply_source_formatting'])) {
    $settings['apply_source_formatting'] = $config['apply_source_formatting'];
  }

  if (isset($config['css_setting'])) {
    // Versions below 3.0.1 could only handle one stylesheet.
    if (version_compare($editor['installed version'], '3.0.1.4391', '<')) {
      if ($config['css_setting'] == 'theme') {
        $settings['contentsCss'] = reset(wysiwyg_get_css());
      }
      elseif ($config['css_setting'] == 'self' && isset($config['css_path'])) {
        $settings['contentsCss'] = strtr($config['css_path'], array('%b' => base_path(), '%t' => path_to_theme()));
      }
    }
    else {
      if ($config['css_setting'] == 'theme') {
        $settings['contentsCss'] = wysiwyg_get_css();
      }
      elseif ($config['css_setting'] == 'self' && isset($config['css_path'])) {
        $settings['contentsCss'] = explode(',', strtr($config['css_path'], array('%b' => base_path(), '%t' => path_to_theme())));
      }
    }
  }

  if (isset($config['language'])) {
    $settings['language'] = $config['language'];
  }
  if (isset($config['resizing'])) {
    // CKEditor tests "!== false", so ensure it is a Boolean.
    $settings['resize_enabled'] = (bool) $config['resizing'];
  }
  if (isset($config['toolbar_loc'])) {
    $settings['toolbarLocation'] = $config['toolbar_loc'];
  }

  $settings['toolbar'] = array();
  if (!empty($config['buttons'])) {
    $extra_plugins = array();
    $plugins = wysiwyg_get_plugins($editor['name']);
    foreach ($config['buttons'] as $plugin => $buttons) {
      foreach ($buttons as $button => $enabled) {
        // Iterate separately over buttons and extensions properties.
        foreach (array('buttons', 'extensions') as $type) {
          // Skip unavailable plugins.
          if (!isset($plugins[$plugin][$type][$button])) {
            continue;
          }
          // Add buttons.
          if ($type == 'buttons') {
            $settings['toolbar'][] = $button;
          }
          // Add external Drupal plugins to the list of extensions.
          if ($type == 'buttons' && !empty($plugins[$plugin]['proxy'])) {
            $extra_plugins[] = $button;
          }
          // Add external plugins to the list of extensions.
          elseif ($type == 'buttons' && empty($plugins[$plugin]['internal'])) {
            $extra_plugins[] = $plugin;
          }
          // Add internal buttons that also need to be loaded as extension.
          elseif ($type == 'buttons' && !empty($plugins[$plugin]['load'])) {
            $extra_plugins[] = $plugin;
          }
          // Add plain extensions.
          elseif ($type == 'extensions' && !empty($plugins[$plugin]['load'])) {
            $extra_plugins[] = $plugin;
          }
          // Allow plugins to add or override global configuration settings.
          if (!empty($plugins[$plugin]['options'])) {
            $settings = array_merge($settings, $plugins[$plugin]['options']);
          }
        }
      }
    }
    if (!empty($extra_plugins)) {
      $settings['extraPlugins'] = implode(',', $extra_plugins);
    }
  }
  // For now, all buttons are placed into one row.
  $settings['toolbar'] = array($settings['toolbar']);

  return $settings;
}

/**
 * Build a JS settings array of native external plugins that need to be loaded separately.
 */
function wysiwyg_ckeditor_plugin_settings($editor, $profile, $plugins) {
  $settings = array();
  foreach ($plugins as $name => $plugin) {
    // Register all plugins that need to be loaded.
    if (!empty($plugin['load'])) {
      $settings[$name] = array();
      // Add path for native external plugins.
      if (empty($plugin['internal']) && isset($plugin['path'])) {
        $settings[$name]['path'] = base_path() . $plugin['path'] . '/';
      }
      // Force native internal plugins to use the standard path.
      else {
        $settings[$name]['path'] = base_path() . $editor['library path'] . '/plugins/' . $name . '/';
      }
      // CKEditor defaults to 'plugin.js' on its own when filename is not set.
      if (!empty($plugin['filename'])) {
        $settings[$name]['fileName'] = $plugin['filename'];
      }
    }
  }
  return $settings;
}

/**
 * Build a JS settings array for Drupal plugins loaded via the proxy plugin.
 */
function wysiwyg_ckeditor_proxy_plugin_settings($editor, $profile, $plugins) {
  $settings = array();
  foreach ($plugins as $name => $plugin) {
    // Populate required plugin settings.
    $settings[$name] = $plugin['dialog settings'] + array(
      'title' => $plugin['title'],
      'icon' => base_path() . $plugin['icon path'] . '/' . $plugin['icon file'],
      'iconTitle' => $plugin['icon title'],
      // @todo These should only be set if the plugin defined them.
      'css' => base_path() . $plugin['css path'] . '/' . $plugin['css file'],
    );
  }
  return $settings;
}

/**
 * Return internal plugins for this editor; semi-implementation of hook_wysiwyg_plugin().
 */
function wysiwyg_ckeditor_plugins($editor) {
  $plugins = array(
    'default' => array(
      'buttons' => array(
        'Bold' => t('Bold'), 'Italic' => t('Italic'), 'Underline' => t('Underline'),
        'Strike' => t('Strike-through'),
        'JustifyLeft' => t('Align left'), 'JustifyCenter' => t('Align center'), 'JustifyRight' => t('Align right'), 'JustifyBlock' => t('Justify'),
        'BulletedList' => t('Bullet list'), 'NumberedList' => t('Numbered list'),
        'Outdent' => t('Outdent'), 'Indent' => t('Indent'),
        'Undo' => t('Undo'), 'Redo' => t('Redo'),
        'Link' => t('Link'), 'Unlink' => t('Unlink'), 'Anchor' => t('Anchor'),
        'Image' => t('Image'),
        'TextColor' => t('Forecolor'), 'BGColor' => t('Backcolor'),
        'Superscript' => t('Superscript'), 'Subscript' => t('Subscript'),
        'Blockquote' => t('Blockquote'), 'Source' => t('Source code'),
        'HorizontalRule' => t('Horizontal rule'),
        'Cut' => t('Cut'), 'Copy' => t('Copy'), 'Paste' => t('Paste'),
        'PasteText' => t('Paste Text'), 'PasteFromWord' => t('Paste from Word'),
        'ShowBlocks' => t('Show blocks'),
        'RemoveFormat' => t('Remove format'),
        'SpecialChar' => t('Character map'),
        'Format' => t('HTML block format'), 'Font' => t('Font'), 'FontSize' => t('Font size'), 'Styles' => t('Font style'),
        'Table' => t('Table'),
        'SelectAll' => t('Select all'), 'Find' => t('Search'), 'Replace' => t('Replace'),
        'Flash' => t('Flash'), 'Smiley' => t('Smiley'),
        'CreateDiv' => t('Div container'),
        'Maximize' => t('Maximize'),
        'SpellChecker' => t('Check spelling'), 'Scayt' => t('Check spelling as you type'),
        'About' => t('About'),
      ),
      'internal' => TRUE,
    ),
  );

  if (version_compare($editor['installed version'], '3.1.0.4885', '<')) {
    unset($plugins['default']['buttons']['CreateDiv']);
  }
  return $plugins;
}

