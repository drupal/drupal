<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\Block.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 block source from database.
 *
 * @MigrateSource(
 *   id = "d6_block"
 * )
 */
class Block extends DrupalSqlBase {
  /**
   * The default theme name.
   *
   * @var string
   */
  protected $defaultTheme;

  /**
   * The admin theme name.
   *
   * @var string
   */
  protected $adminTheme;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('blocks', 'b')
      ->fields('b', array('bid', 'module', 'delta', 'theme', 'status', 'weight', 'region', 'visibility', 'pages', 'title', 'cache'))
      ->orderBy('bid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function runQuery() {
    $this->defaultTheme = $this->variableGet('theme_default', 'Garland');
    $this->adminTheme = $this->variableGet('admin_theme', null);
    return parent::runQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'bid' => $this->t('The block numeric identifier.'),
      'module' => $this->t('The module providing the block.'),
      'delta' => $this->t('The block\'s delta.'),
      'theme' => $this->t('Which theme the block is placed in.'),
      'status' => $this->t('Whether or not the block is enabled.'),
      'weight' => $this->t('Weight of the block for ordering within regions.'),
      'region' => $this->t('Region the block is placed in.'),
      'visibility' => $this->t('Visibility expression.'),
      'pages' => $this->t('Pages list.'),
      'title' => $this->t('Block title.'),
      'cache' => $this->t('Cache rule.'),

    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('default_theme', $this->defaultTheme);
    $row->setSourceProperty('admin_theme', $this->adminTheme);
    $module = $row->getSourceProperty('module');
    $delta = $row->getSourceProperty('delta');
    $roles = $this->select('blocks_roles', 'br')
      ->fields('br', array('rid'))
      ->condition('module', $module)
      ->condition('delta', $delta)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('permissions', $roles);
    $settings = array();
    // Contrib can use hook_migration_d6_block_prepare_row() to add similar
    // variables via $migration->getSource()->variableGet().
    switch ($module) {
      case 'aggregator':
        list($type, $id) = explode('-', $delta);
        if ($type == 'feed') {
          $item_count = $this->database->query('SELECT block FROM {aggregator_feed} WHERE fid = :fid', array(':fid' => $id))->fetchField();
        }
        else {
          $item_count = $this->database->query('SELECT block FROM {aggregator_category} WHERE cid = :cid', array(':cid' => $id))->fetchField();
        }
        $settings['aggregator']['item_count'] = $item_count;
        break;
      case 'book':
        $settings['book']['block_mode'] = $this->variableGet('book_block_mode', 'all pages');
        break;
      case 'forum':
        $settings['forum']['block_num'] = $this->variableGet('forum_block_num_'. $delta, 5);
        break;
      case 'statistics':
        foreach (array('statistics_block_top_day_num', 'statistics_block_top_all_num', 'statistics_block_top_last_num') as $name) {
          $settings['statistics'][$name] = $this->variableGet($name, 0);
        }
        break;
      case 'user':
        switch ($delta) {
          case 2:
            $settings['user']['block_whois_new_count'] = $this->variableGet('user_block_whois_new_count', 5);
            break;
          case 3:
            $settings['user']['block_seconds_online'] = $this->variableGet('user_block_seconds_online', 900);
            $settings['user']['max_list_count'] = $this->variableGet('user_block_max_list_count', 10);
            break;
        }
        break;
    }
    $row->setSourceProperty('settings', $settings);
    return parent::prepareRow($row);
  }
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['module']['type'] = 'string';
    $ids['delta']['type'] = 'string';
    $ids['theme']['type'] = 'string';
    return $ids;
  }

}
