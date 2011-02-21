<?php
// $Id: views_content.views.inc,v 1.4 2010/09/07 09:02:51 sdboyer Exp $

/**
 * @file
 * Contains Views plugin definitions for the panel pane display.
 */

/**
 * Implements hook_views_plugins
 */
function views_content_views_plugins() {
  return array(
    'display' => array(
      'panel_pane' => array(
        'title' => t('Content pane'),
        'help' => t('Is available as content for a panel or dashboard display.'),
        'handler' => 'views_content_plugin_display_panel_pane',
        'path' => drupal_get_path('module', 'views_content') . '/plugins/views',
        'theme path' => drupal_get_path('module', 'views') . '/theme',
        'theme' => 'views_view',
        'use ajax' => TRUE,
        'use pager' => TRUE,
        'use more' => TRUE,
        'accept attachments' => TRUE,
        'help topic' => 'display-pane',
      ),
      'ctools_context' => array(
        'title' => t('Context'),
        'help' => t('Makes the view results available as a context for use in Panels and other applications.'),
        'handler' => 'views_content_plugin_display_ctools_context',
        'path' => drupal_get_path('module', 'views_content') . '/plugins/views',
        'theme path' => drupal_get_path('module', 'views') . '/theme',
        'theme' => 'views_view',
        'use ajax' => FALSE,
        'use pager' => TRUE,
        'use more' => FALSE,
        'accept attachments' => TRUE,
        'returns context' => TRUE,
        'help topic' => 'display-context',
      ),
    ),
    'style' => array(
      'ctools_context' => array(
        'title' => t('Context'),
        'help' => t('Contains rows in contexts.'),
        'handler' => 'views_content_plugin_style_ctools_context',
        'path' => drupal_get_path('module', 'views_content') . '/plugins/views',
        'theme path' => drupal_get_path('module', 'views') . '/theme',
        'theme' => 'views_view_unformatted',
        'uses row plugin' => TRUE,
        'uses fields' => TRUE,
        'uses options' => TRUE,
        'type' => 'context',
        'help topic' => 'style-context',
      ),
    ),
  );
}
