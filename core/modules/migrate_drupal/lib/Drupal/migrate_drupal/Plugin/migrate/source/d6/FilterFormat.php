<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\FilterFormats.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 6 role source from database.
 *
 * @MigrateSource(
 *   id = "d6_filter_format"
 * )
 */
class FilterFormat extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('filter_formats', 'f')
      ->fields('f', array('format', 'name', 'roles', 'cache'))
      ->orderBy('format');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'format' => $this->t('Format ID.'),
      'name' => $this->t('The name of the filter format.'),
      'roles' => $this->t('The user roles that can use the format.'),
      'cache' => $this->t('Flag to indicate whether format is cachable. (1 = cachable, 0 = not cachable).'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $filters = array();
    $roles = $row->getSourceProperty('roles');
    $row->setSourceProperty('roles', array_values(array_filter(explode(',', $roles))));
    $format = $row->getSourceProperty('format');
    // Find filters for this row.
    $results = $this->database
      ->select('filters', 'f', array('fetch' => \PDO::FETCH_ASSOC))
      ->fields('f', array('module', 'delta', 'weight'))
      ->condition('format', $format)
      ->execute();
    foreach ($results as $raw_filter) {
      $module = $raw_filter['module'];
      $delta = $raw_filter['delta'];
      $filter = array(
        'module' => $module,
        'delta' => $delta,
        'weight' => $raw_filter['weight'],
        'settings' => array(),
      );
      // Load the filter settings for the filter module, modules can use
      // hook_migration_d6_filter_formats_prepare_row() to add theirs.
      if ($raw_filter['module'] == 'filter') {
        if (!$delta) {
          if ($setting = $this->variableGet("allowed_html_$format", NULL)) {
            $filter['settings']['allowed_html'] = $setting;
          }
          if ($setting = $this->variableGet("filter_html_help_$format", NULL)) {
            $filter['settings']['filter_html_help'] = $setting;
          }
          if ($setting = $this->variableGet("filter_html_nofollow_$format", NULL)) {
            $filter['settings']['filter_html_nofollow'] = $setting;
          }
        }
        elseif ($delta == 2 && ($setting = $this->variableGet("filter_url_length_$format", NULL))) {
          $filter['settings']['filter_url_length'] = $setting;
        }
      }
      $filters[] = $filter;
    }

    $row->setSourceProperty('filters', $filters);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['format']['type'] = 'integer';
    return $ids;
  }

}


