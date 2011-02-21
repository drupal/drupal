<?php
// $Id: page_manager_context.inc,v 1.1 2010/10/11 22:18:24 sdboyer Exp $

/**
 * @file
 * A page_manager cache indirection mechanism that just attaches context
 * caching to the larger page cache.
 */

$plugin = array(
  // cache plugins are the rare plugin types that have no real UI but
  // we're providing a title just in case.
  'title' => t('Page manager context'),
  'cache get' => 'page_manager_cache_page_manager_context_cache_get',
  'cache set' => 'page_manager_cache_page_manager_context_cache_set',
  'cache finalize' => 'page_manager_cache_page_manager_context_cache_finalize',

  // We don't support a clear because the general uses of clear have no effect
  // on us.
);

function page_manager_cache_page_manager_context_cache_get($data, $key) {
  $page = page_manager_get_page_cache($data);
  if ($page) {
    if (!empty($page->context_cache[$key])) {
      return $page->context_cache[$key];
    }
    else {
      ctools_include('context-task-handler');
      if ($key == 'temp') {
        $handler = $page->new_handler;
      }
      else {
        $handler = $page->handlers[$key];
      }
      return ctools_context_handler_get_task_object($page->task, $page->subtask, $handler);
    }
  }
}

function page_manager_cache_page_manager_context_cache_set($data, $key, $object) {
  $page = page_manager_get_page_cache($data);
  if ($page) {
    $page->context_cache[$key] = $object;
    return page_manager_set_page_cache($page);
  }
}

/**
 * Copy temporary data from the page manager cache
 */
function page_manager_cache_page_manager_context_cache_finalize($data, $key, $object) {
  // Statically cached so there shouldn't be any worries. It's an object so
  // referencing ensures that we'll get the right one.
  $page = page_manager_get_page_cache($data);
  if ($page) {
    if ($key == 'temp') {
      $handler = $page->new_handler;
    }
    else {
      $handler = $page->handlers[$key];
    }
    $handler->conf['contexts'] = $object->contexts;
    $handler->conf['relationships'] = $object->relationships;

    if (isset($page->context_cache[$key])) {
      unset($page->context_cache[$key]);
    }
    return page_manager_set_page_cache($page);
  }
}
