<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\MenuHierarchy.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\views\Views;

/**
 * Sort in menu hierarchy order.
 *
 * Given a field name of 'p' this produces an ORDER BY on p1, p2, ..., p9;
 * and optionally injects multiple joins to {menu_links} to sort by weight
 * and title as well.
 *
 * This is only really useful for the {menu_links} table.
 *
 * @ViewsSort("menu_hierarchy")
 */
class MenuHierarchy extends SortPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['sort_within_level'] = array('default' => FALSE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['sort_within_level'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sort within each hierarchy level'),
      '#description' => t('Enable this to sort the items within each level of the hierarchy by weight and title.  Warning: this may produce a slow query.'),
      '#default_value' => $this->options['sort_within_level'],
    );
  }

  public function query() {
    $this->ensureMyTable();
    $max_depth = isset($this->definition['max depth']) ? $this->definition['max depth'] : MENU_MAX_DEPTH;
    for ($i = 1; $i <= $max_depth; ++$i) {
      if ($this->options['sort_within_level']) {
        $definition = array(
          'table' => 'menu_links',
          'field' => 'mlid',
          'left_table' => $this->tableAlias,
          'left_field' => $this->field . $i
        );
        $join = Views::pluginManager('join')->createInstance('standard', $definition);

        $menu_links = $this->query->addTable('menu_links', NULL, $join);
        $this->query->addOrderBy($menu_links, 'weight', $this->options['order']);
        $this->query->addOrderBy($menu_links, 'link_title', $this->options['order']);
      }

      // We need this even when also sorting by weight and title, to make sure
      // that children of two parents with the same weight and title are
      // correctly separated.
      $this->query->addOrderBy($this->tableAlias, $this->field . $i, $this->options['order']);
    }
  }

}
