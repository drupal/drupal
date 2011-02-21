<?php
// $Id: views.drush.inc,v 1.1.6.2 2011/01/05 00:09:12 dereine Exp $

/**
 * @file
 * Drush integration of views.
 */

/**
 * Adds a cache clear option for views.
 */
function views_drush_cache_clear(&$types) {
  $types['views'] = 'views_invalidate_cache';
}