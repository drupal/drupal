<?php

namespace Drupal\filter\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 6 filter source from database.
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
    return $this->select('filter_formats', 'f')->fields('f');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'format' => $this->t('Format ID.'),
      'name' => $this->t('The name of the format.'),
      'cache' => $this->t('Whether the format is cacheable.'),
      'roles' => $this->t('The role IDs which can use the format.'),
      'filters' => $this->t('The filters configured for the format.'),
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
    $results = $this->select('filters', 'f')
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
