<?php

/**
 * @file
 * Definition of views_handler_relationship_translation.
 */

/**
 * Handles relationships for content translation sets and provides multiple
 * options.
 *
 * @ingroup views_relationship_handlers
 */
class views_handler_relationship_translation extends views_handler_relationship {
  function option_definition() {
    $options = parent::option_definition();
    $options['language'] = array('default' => 'current');

    return $options;
  }

  /**
   * Add a translation selector.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $options = array(
      'all' => t('All'),
      'current' => t('Current language'),
      'default' => t('Default language'),
    );
    $options = array_merge($options, locale_language_list());
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
  function query() {
    // Figure out what base table this relationship brings to the party.
    $table_data = views_fetch_data($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $this->ensure_my_table();

    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $base_field;
    $def['left_table'] = $this->table_alias;
    $def['left_field'] = $this->field;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    $def['extra'] = array();
    if ($this->options['language'] != 'all') {
      switch ($this->options['language']) {
        case 'current':
          $def['extra'][] = array(
            'field' => 'language',
            'value' => '***CURRENT_LANGUAGE***',
          );
          break;
        case 'default':
          $def['extra'][] = array(
            'field' => 'language',
            'value' => '***DEFAULT_LANGUAGE***',
          );
          break;
        // Other values will be the language codes.
        default:
          $def['extra'][] = array(
            'field' => 'language',
            'value' => $this->options['language'],
          );
          break;
      }
    }

    if (!empty($def['join_handler']) && class_exists($def['join_handler'])) {
      $join = new $def['join_handler'];
    }
    else {
      $join = new views_join();
    }

    $join->definition = $def;
    $join->construct();
    $join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, $this->definition['base'], $this->relationship);
  }
}
