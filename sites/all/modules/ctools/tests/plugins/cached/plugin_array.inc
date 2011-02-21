<?php
// $Id: plugin_array.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $
/**
 * @file
 * Chaos Tools plugin include using a plugin array to declare a plugin.
 */

/**
 * Plugin array plugin definition.
 */
$plugin = array(
  'function' => 'ctools_plugin_test_plugin_array_cached_test',
  'handler' => array(
    'class' => 'ctoolsCachedPluginArray',
  ),
);

/**
 * Plugin array function plugin.
 */
function ctools_plugin_test_plugin_array_cached_test() {}
