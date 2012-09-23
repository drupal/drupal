<?php

/**
 * @file
 * Token integration for the views module.
 */

/**
 * Implements hook_token_info().
 */
function views_token_info() {
  $info['types']['view'] = array(
    'name' => t('View'),
    'description' => t('Tokens related to views.'),
    'needs-data' => 'view',
  );
  $info['tokens']['view']['name'] = array(
    'name' => t('Name'),
    'description' => t('The human-readable name of the view.'),
  );
  $info['tokens']['view']['description'] = array(
    'name' => t('Description'),
    'description' => t('The description of the view.'),
  );
  $info['tokens']['view']['machine-name'] = array(
    'name' => t('Machine name'),
    'description' => t('The machine-readable name of the view.'),
  );
  $info['tokens']['view']['title'] = array(
    'name' => t('Title'),
    'description' => t('The title of current display of the view.'),
  );
  $info['tokens']['view']['url'] = array(
    'name' => t('URL'),
    'description' => t('The URL of the view.'),
    'type' => 'url',
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function views_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);
  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
  }
  $sanitize = !empty($options['sanitize']);
  $langcode = isset($options['language']) ? $options['language']->langcode : NULL;

  $replacements = array();

  if ($type == 'view' && !empty($data['view'])) {
    $view = $data['view'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $replacements[$original] = $sanitize ? check_plain($view->storage->getHumanName()) : $view->storage->getHumanName();
          break;

        case 'description':
          $replacements[$original] = $sanitize ? check_plain($view->storage->description) : $view->storage->description;
          break;

        case 'machine-name':
          $replacements[$original] = $view->storage->name;
          break;

        case 'title':
          $title = $view->getTitle();
          $replacements[$original] = $sanitize ? check_plain($title) : $title;
          break;

        case 'url':
          if ($path = $view->getUrl()) {
            $replacements[$original] = url($path, $url_options);
          }
          break;
      }
    }

    // [view:url:*] nested tokens. This only works if Token module is installed.
    if ($url_tokens = token_find_with_prefix($tokens, 'url')) {
      if ($path = $view->getUrl()) {
        $replacements += token_generate('url', $url_tokens, array('path' => $path), $options);
      }
    }
  }

  return $replacements;
}
