<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\ViewMode.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

/**
 * The view mode source.
 *
 * @MigrateSource(
 *   id = "d6_view_mode",
 *   source_provider = "content"
 * )
 */
class ViewMode extends ViewModeBase {

  /**
   * {@inheritdoc}
   */
  protected function runQuery() {
    $rows = array();
    $result = $this->prepareQuery()->execute();
    while ($field_row = $result->fetchAssoc()) {
      $field_row['display_settings'] = unserialize($field_row['display_settings']);
      foreach ($this->getViewModes() as $view_mode) {
        if (isset($field_row['display_settings'][$view_mode]) && !$field_row['display_settings'][$view_mode]['exclude']) {
          if (!isset($rows[$view_mode])) {
            $rows[$view_mode]['entity_type'] = 'node';
            $rows[$view_mode]['view_mode'] = $view_mode;
          }
        }
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('content_node_field_instance', 'cnfi')
      ->fields('cnfi', array(
        'display_settings',
      ));

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'display_settings' => $this->t('Serialize data with display settings.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['view_mode']['type'] = 'string';
    return $ids;
  }

}
