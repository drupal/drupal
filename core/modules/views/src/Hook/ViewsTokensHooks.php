<?php

namespace Drupal\views\Hook;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views.
 */
class ViewsTokensHooks {

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $info['types']['view'] = [
      'name' => t('View', [], [
        'context' => 'View entity type',
      ]),
      'description' => t('Tokens related to views.'),
      'needs-data' => 'view',
    ];
    $info['tokens']['view']['label'] = ['name' => t('Label'), 'description' => t('The label of the view.')];
    $info['tokens']['view']['description'] = ['name' => t('Description'), 'description' => t('The description of the view.')];
    $info['tokens']['view']['id'] = ['name' => t('ID'), 'description' => t('The machine-readable ID of the view.')];
    $info['tokens']['view']['title'] = [
      'name' => t('Title'),
      'description' => t('The title of current display of the view.'),
    ];
    $info['tokens']['view']['url'] = ['name' => t('URL'), 'description' => t('The URL of the view.'), 'type' => 'url'];
    $info['tokens']['view']['base-table'] = [
      'name' => t('Base table'),
      'description' => t('The base table used for this view.'),
    ];
    $info['tokens']['view']['base-field'] = [
      'name' => t('Base field'),
      'description' => t('The base field used for this view.'),
    ];
    $info['tokens']['view']['total-rows'] = [
      'name' => t('Total rows'),
      'description' => t('The total amount of results returned from the view. The current display will be used.'),
    ];
    $info['tokens']['view']['items-per-page'] = [
      'name' => t('Items per page'),
      'description' => t('The number of items per page.'),
    ];
    $info['tokens']['view']['current-page'] = [
      'name' => t('Current page'),
      'description' => t('The current page of results the view is on.'),
    ];
    $info['tokens']['view']['page-count'] = ['name' => t('Page count'), 'description' => t('The total page count.')];
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $url_options = ['absolute' => TRUE];
    if (isset($options['language'])) {
      $url_options['language'] = $options['language'];
    }
    $replacements = [];
    if ($type == 'view' && !empty($data['view'])) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $data['view'];
      $bubbleable_metadata->addCacheableDependency($view->storage);
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'label':
            $replacements[$original] = $view->storage->label();
            break;

          case 'description':
            $replacements[$original] = $view->storage->get('description');
            break;

          case 'id':
            $replacements[$original] = $view->storage->id();
            break;

          case 'title':
            $title = $view->getTitle();
            $replacements[$original] = $title;
            break;

          case 'url':
            try {
              if ($url = $view->getUrl()) {
                $replacements[$original] = $url->setOptions($url_options)->toString();
              }
            }
            catch (\InvalidArgumentException) {
              // The view has no URL so we leave the value empty.
              $replacements[$original] = '';
            }
            break;

          case 'base-table':
            $replacements[$original] = $view->storage->get('base_table');
            break;

          case 'base-field':
            $replacements[$original] = $view->storage->get('base_field');
            break;

          case 'total-rows':
            $replacements[$original] = (int) $view->total_rows;
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
            $replacements[$original] = max(1, (int) ceil($view->total_rows / $per_page));
            break;
        }
      }
    }
    return $replacements;
  }

}
