<?php
// $Id: ctools_export_ui.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * The default plugin exists only to provide the base class. Other plugins
 * which do not provide a class will get this class instead. Any classes
 * provided should use this class as their parent:
 *
 * @code
 *   'handler' => array(
 *     'class' => 'ctools_export_ui_mine',
 *     'parent' => 'ctools_export_ui',
 *   ),
 * @endcode
 *
 * Using the above notation will ensure that this plugin's is loaded before
 * the child plugin's class and avoid whitescreens.
 */
$plugin = array(
  // As this is the base class plugin, it shouldn't declare any menu items.
  'has menu' => FALSE,
  'handler' => array(
    'class' => 'ctools_export_ui',
  ),
);
