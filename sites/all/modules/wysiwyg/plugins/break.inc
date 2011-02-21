<?php
// $Id: break.inc,v 1.4 2009/06/09 00:18:12 sun Exp $

/**
 * @file
 * Wysiwyg API integration on behalf of Node module.
 */

/**
 * Implementation of hook_wysiwyg_plugin().
 */
function wysiwyg_break_plugin() {
  $plugins['break'] = array(
    'title' => t('Teaser break'),
    'vendor url' => 'http://drupal.org/project/wysiwyg',
    'icon file' => 'break.gif',
    'icon title' => t('Separate the teaser and body of this content'),
    'settings' => array(),
  );
  return $plugins;
}

