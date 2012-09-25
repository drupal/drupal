<?php

/**
 * @file
 * Definition of Views\translation\Plugin\views\relationship\Translation.
 */

namespace Views\translation\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Handles relationships for content translation sets and provides multiple
 * options.
 *
 * @ingroup views_relationship_handlers
 *
 * @Plugin(
 *   id = "translation",
 *   module = "translation"
 * )
 */
class Translation extends RelationshipPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['language'] = array('default' => 'current');

    return $options;
  }

  /**
   * Add a translation selector.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = array(
      'all' => t('All'),
      'current' => t('Current language'),
      'default' => t('Default language'),
    );
    $options = array_merge($options, views_language_list());
    $form['language'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->options['language'],
      '#title' => t('Translation option'),
      '#description' => t('The translation options allows you to select which translation or translations in a translation set join on. Select "Current language" or "Default language" to join on the translation in the current or default language respectively. Select a specific language to join on a translation in that language. If you select "All", each translation will create a new row, which may appear to cause duplicates.'),
    );
  }

  /**
   * Called to implement a relationship in a query.
   */
  public function query() {
    // Figure out what base table this relationship brings to the party.
    $table_data = views_fetch_data($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $this->ensureMyTable();

    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $base_field;
    $def['left_table'] = $this->tableAlias;
    $def['left_field'] = $this->field;
    $def['adjusted'] = TRUE;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    $def['extra'] = array();
    if ($this->options['language'] != 'all') {
      switch ($this->options['language']) {
        case 'current':
          $def['extra'][] = array(
            'field' => 'langcode',
            'value' => '***CURRENT_LANGUAGE***',
          );
          break;
        case 'default':
          $def['extra'][] = array(
            'field' => 'langcode',
            'value' => '***DEFAULT_LANGUAGE***',
          );
          break;
        // Other values will be the language codes.
        default:
          $def['extra'][] = array(
            'field' => 'langcode',
            'value' => $this->options['language'],
          );
          break;
      }
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance($id, $def);

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, $this->definition['base'], $this->relationship);
  }

}
