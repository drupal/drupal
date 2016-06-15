<?php

namespace Drupal\block\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal block source from database.
 *
 * @MigrateSource(
 *   id = "block",
 *   source_provider = "block"
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
   * Table containing block configuration.
   *
   * @var string
   */
  protected $blockTable;

  /**
   * Table mapping blocks to user roles.
   *
   * @var string
   */
  protected $blockRoleTable;

  /**
   * Table listing user roles.
   *
   * @var string
   */
  protected $userRoleTable;

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->getModuleSchemaVersion('system') >= 7000) {
      $this->blockTable = 'block';
      $this->blockRoleTable = 'block_role';
    }
    else {
      $this->blockTable = 'blocks';
      $this->blockRoleTable = 'blocks_roles';
    }
    // Drupal 6 & 7 both use the same name for the user roles table.
    $this->userRoleTable = 'role';

    return $this->select($this->blockTable, 'b')->fields('b');
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->defaultTheme = $this->variableGet('theme_default', 'Garland');
    $this->adminTheme = $this->variableGet('admin_theme', NULL);
    return parent::initializeIterator();
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
  public function getIds() {
    $ids['module']['type'] = 'string';
    $ids['delta']['type'] = 'string';
    $ids['theme']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('default_theme', $this->defaultTheme);
    $row->setSourceProperty('admin_theme', $this->adminTheme);

    $module = $row->getSourceProperty('module');
    $delta = $row->getSourceProperty('delta');

    $query = $this->select($this->blockRoleTable, 'br')
      ->fields('br', array('rid'))
      ->condition('module', $module)
      ->condition('delta', $delta);
    $query->join($this->userRoleTable, 'ur', 'br.rid = ur.rid');
    $roles = $query->execute()
      ->fetchCol();
    $row->setSourceProperty('roles', $roles);

    $settings = array();
    switch ($module) {
      case 'aggregator':
        list($type, $id) = explode('-', $delta);
        if ($type == 'feed') {
          $item_count = $this->select('aggregator_feed', 'af')
            ->fields('af', ['block'])
            ->condition('fid', $id)
            ->execute()
            ->fetchField();
        }
        else {
          $item_count = $this->select('aggregator_category', 'ac')
            ->fields('ac', ['block'])
            ->condition('cid', $id)
            ->execute()
            ->fetchField();
        }
        $settings['aggregator']['item_count'] = $item_count;
        break;
      case 'book':
        $settings['book']['block_mode'] = $this->variableGet('book_block_mode', 'all pages');
        break;
      case 'forum':
        $settings['forum']['block_num'] = $this->variableGet('forum_block_num_' . $delta, 5);
        break;
      case 'statistics':
        foreach (array('statistics_block_top_day_num', 'statistics_block_top_all_num', 'statistics_block_top_last_num') as $name) {
          $settings['statistics'][$name] = $this->variableGet($name, 0);
        }
        break;
      case 'user':
        switch ($delta) {
          case 2:
          case 'new':
            $settings['user']['block_whois_new_count'] = $this->variableGet('user_block_whois_new_count', 5);
            break;
          case 3:
          case 'online':
            $settings['user']['block_seconds_online'] = $this->variableGet('user_block_seconds_online', 900);
            $settings['user']['max_list_count'] = $this->variableGet('user_block_max_list_count', 10);
            break;
        }
        break;
    }
    $row->setSourceProperty('settings', $settings);
    return parent::prepareRow($row);
  }

}
