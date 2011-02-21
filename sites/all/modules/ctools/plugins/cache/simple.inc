<?php
// $Id: simple.inc,v 1.1 2010/10/11 22:18:24 sdboyer Exp $

/**
 * @file
 * A simple cache indirection mechanism that just uses the basic object cache.
 */

$plugin = array(
  // cache plugins are the rare plugin types that have no real UI but
  // we're providing a title just in case.
  'title' => t('Simple'),
  'cache get' => 'ctools_cache_simple_cache_get',
  'cache set' => 'ctools_cache_simple_cache_set',
  'cache clear' => 'ctools_cache_simple_cache_clear',
);

function ctools_cache_simple_cache_get($data, $key) {
  // Ensure that if there is somehow no data, we at least don't stomp on other
  // people's caches.
  if (empty($data)) {
    $data = 'simple_cache_plugin';
  }

  return ctools_object_cache_get($data, $key);
}

function ctools_cache_simple_cache_set($data, $key, $object) {
  // Ensure that if there is somehow no data, we at least don't stomp on other
  // people's caches.
  if (empty($data)) {
    $data = 'simple_cache_plugin';
  }

  return ctools_object_cache_set($data, $key, $object);
}

function ctools_cache_simple_cache_clear($data, $key) {
  // Ensure that if there is somehow no data, we at least don't stomp on other
  // people's caches.
  if (empty($data)) {
    $data = 'simple_cache_plugin';
  }

  return ctools_object_cache_clear($data, $key);
}
