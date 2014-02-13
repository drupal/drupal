<?php

/**
 * @file
 * Token integration for the views module.
 */

use Drupal\Component\Utility\String;

/**
 * Implements hook_token_info().
 */
function views_token_info() {
  $info['types']['view'] = array(
    'name' => t('View'),
    'description' => t('Tokens related to views.'),
    'needs-data' => 'view',
  );
  $info['tokens']['view']['label'] = array(
    'name' => t('Label'),
    'description' => t('The label of the view.'),
  );
  $info['tokens']['view']['description'] = array(
    'name' => t('Description'),
    'description' => t('The description of the view.'),
  );
  $info['tokens']['view']['id'] = array(
    'name' => t('ID'),
    'description' => t('The machine-readable ID of the view.'),
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
  $info['tokens']['view']['base-table'] = array(
    'name' => t('Base table'),
    'description' => t('The base table used for this view.'),
  );
  $info['tokens']['view']['base-field'] = array(
    'name' => t('Base field'),
    'description' => t('The base field used for this view.'),
  );
  $info['tokens']['view']['total-rows'] = array(
    'name' => t('Total rows'),
    'description' => t('The total amount of results returned from the view. The current display will be used.'),
  );
  $info['tokens']['view']['items-per-page'] = array(
    'name' => t('Items per page'),
    'description' => t('The number of items per page.'),
  );
  $info['tokens']['view']['current-page'] = array(
    'name' => t('Current page'),
    'description' => t('The current page of results the view is on.'),
  );
  $info['tokens']['view']['page-count'] = array(
    'name' => t('Page count'),
    'description' => t('The total page count.'),
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function views_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'view' && !empty($data['view'])) {
    $view = $data['view'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'label':
          $replacements[$original] = $sanitize ? String::checkPlain($view->storage->label()) : $view->storage->label();
          break;

        case 'description':
          $replacements[$original] = $sanitize ? String::checkPlain($view->storage->get('description')) : $view->storage->get('description');
          break;

        case 'id':
          $replacements[$original] = $view->storage->id();
          break;

        case 'title':
          $title = $view->getTitle();
          $replacements[$original] = $sanitize ? String::checkPlain($title) : $title;
          break;

        case 'url':
          if ($path = $view->getUrl()) {
            $replacements[$original] = url($path, $url_options);
          }
          break;
        case 'base-table':
          $replacements[$original] = $view->storage->get('base_table');
          break;
        case 'base-field':
          $replacements[$original] = $view->storage->get('base_field');
          break;
        case 'total-rows':
          $replacements[$original] = count($view->result);
          break;
        case 'items-per-page':
          $replacements[$original] = (int) $view->getItemsPerPage();
          break;
        case 'current-page':
          $replacements[$original] = (int) $view->getCurrentPage() + 1;
          break;
        case 'page-count':
          // If there are no items per page, set this to 1 for the division.
          $per_page = $view->getItemsPerPage() ?: 1;
          $replacements[$original] = (int) ceil(count($view->result) / $per_page);
          break;
      }
    }

    // [view:url:*] nested tokens. This only works if Token module is installed.
    if ($url_tokens = $token_service->findWithPrefix($tokens, 'url')) {
      if ($path = $view->getUrl()) {
        $replacements += $token_service->generate('url', $url_tokens, array('path' => $path), $options);
      }
    }
  }

  return $replacements;
}
