<?php

declare(strict_types=1);

namespace Drupal\content_moderation_test_views\Hook;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_moderation_test_views.
 */
class ContentModerationTestViewsHooks {

  /**
   * Implements hook_views_query_alter().
   *
   * @see \Drupal\Tests\content_moderation\Kernel\ViewsModerationStateSortTest::testSortRevisionBaseTable()
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    // Add a secondary sort order to ensure consistent builds when testing click
    // and table sorting.
    if ($view->id() === 'test_content_moderation_state_sort_revision_table') {
      $query->addOrderBy('node_field_revision', 'vid', 'ASC');
    }
  }

  /**
   * Implements hook_views_data_alter().
   *
   * @see \Drupal\Tests\content_moderation\Kernel\ViewsModerationStateFilterTest
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    if (isset($data['users_field_data'])) {
      $data['users_field_data']['uid_revision_test'] = [
        'help' => 'Relate the content revision to the user who created it.',
        'real field' => 'uid',
        'relationship' => [
          'title' => 'Content revision authored',
          'help' => 'Relate the content revision to the user who created it. This relationship will create one record for each content revision item created by the user.',
          'id' => 'standard',
          'base' => 'node_field_revision',
          'base field' => 'uid',
          'field' => 'uid',
          'label' => 'node revisions',
        ],
      ];
    }
  }

}
