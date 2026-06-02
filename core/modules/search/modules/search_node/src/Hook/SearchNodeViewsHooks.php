<?php

namespace Drupal\search_node\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Views hook implementations for Search Node.
 */
class SearchNodeViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    // Add search table, fields, filters, etc., but only if a page using the
    // node_search plugin is enabled.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchPages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'node_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['node_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the node table (or actually, node_field_data).
        // Use a Views table alias to allow other modules to use this table too,
        // if they use the search index.
        $data['node_search_index']['table']['join'] = [
          'node_field_data' => [
            'left_field' => 'nid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "node_search_index.type = 'node_search' AND node_search_index.langcode = node_field_data.langcode",
          ],
        ];

        $data['node_search_total']['table']['join'] = [
          'node_search_index' => [
            'left_field' => 'word',
            'field' => 'word',
          ],
        ];

        $data['node_search_dataset']['table']['join'] = [
          'node_field_data' => [
            'left_field' => 'sid',
            'left_table' => 'node_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'node_search_index.type = node_search_dataset.type AND node_search_index.langcode = node_search_dataset.langcode',
            'type' => 'INNER',
          ],
        ];

        $data['node_search_index']['score'] = [
          'title' => $this->t('Score'),
          'help' => $this->t('The score of the search item. This will not be used if the search filter is not also present.'),
          'field' => [
            'id' => 'search_score',
            'float' => TRUE,
            'no group by' => TRUE,
          ],
          'sort' => [
            'id' => 'search_score',
            'no group by' => TRUE,
          ],
        ];

        $data['node_search_index']['keys'] = [
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
          'filter' => [
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'node_search',
          ],
          'argument' => [
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'node_search',
          ],
        ];

      }
    }

  }

}
