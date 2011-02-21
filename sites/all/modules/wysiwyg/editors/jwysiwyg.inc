<?php
// $Id: jwysiwyg.inc,v 1.5 2010/04/02 14:05:36 sun Exp $

/**
 * @file
 * Editor integration functions for jWYSIWYG.
 */

/**
 * Plugin implementation of hook_editor().
 */
function wysiwyg_jwysiwyg_editor() {
  $editor['jwysiwyg'] = array(
    'title' => 'jWYSIWYG',
    'vendor url' => 'http://code.google.com/p/jwysiwyg/',
    'download url' => 'http://code.google.com/p/jwysiwyg/downloads/list',
    'libraries' => array(
      '' => array(
        'title' => 'Source',
        'files' => array('jquery.wysiwyg.js'),
      ),
      'pack' => array(
        'title' => 'Packed',
        'files' => array('jquery.wysiwyg.pack.js'),
      ),
    ),
    'version callback' => 'wysiwyg_jwysiwyg_version',
    // @todo Wrong property; add separate properties for editor requisites.
    'css path' => wysiwyg_get_path('jwysiwyg'),
    'versions' => array(
      '0.5' => array(
        'js files' => array('jwysiwyg.js'),
        'css files' => array('jquery.wysiwyg.css'),
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
function wysiwyg_jwysiwyg_version($editor) {
  $script = $editor['library path'] . '/jquery.wysiwyg.js';
  if (!file_exists($script)) {
    return;
  }
  $script = fopen($script, 'r');
  fgets($script);
  $line = fgets($script);
  if (preg_match('@([0-9\.]+)$@', $line, $version)) {
    fclose($script);
    return $version[1];
  }
  fclose($script);
}

