<?php
// $Id: views_handler_relationship_node_term_data.inc,v 1.3.4.3 2010/10/07 19:40:02 dereine Exp $
/**
 * @file
 * Views' relationship handlers.
 */

class views_handler_relationship_node_term_data extends views_handler_relationship  {
  function init(&$view, &$options) {
    parent::init($view, $options);

    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_get_vocabularies();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['vocabularies'] = array('default' => array());
    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $vocabularies = taxonomy_get_vocabularies();
    $options = array();
    foreach ($vocabularies as $voc) {
      $options[$voc->machine_name] = check_plain($voc->name);
    }

    $form['vocabularies'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vocabularies'],
      '#description' => t('Choose which vocabularies you wish to relate. Remember that every term found will create a new record, so this relationship is best used on just one vocabulary that has only one term per node.'),
    );
  }

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    $this->ensure_my_table();

    $def = $this->definition;
    $def['table'] = 'taxonomy_term_data';

    if (!array_filter($this->options['vocabularies'])) {
      $term_node = $this->query->add_table('taxonomy_index', $this->relationship);
      $def['left_table'] = 'taxonomy_index';
      $def['left_field'] = 'tid';
      $def['field'] = 'tid';
      $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';
    }
    else {
      // If vocabularies are supplied join a subselect instead
      $def['left_table'] = $this->table_alias;
      $def['left_field'] = 'nid';
      $def['field'] = 'nid';
      $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';

      $query = db_select('taxonomy_term_data', 'td');
      $query->addJoin($def['type'], 'taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
      $query->addJoin($def['type'], 'taxonomy_index', 'tn', 'tn.tid = td.tid');
      $query->condition('tv.machine_name', array_filter($this->options['vocabularies']));
      $query->addTag('term_access');
      $query->fields('td');
      $query->fields('tn', array('nid'));
      $def['table formula'] = $query;
    }

    $join = new views_join();

    $join->definition = $def;
    $join->construct();
    $join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, 'taxonomy_term_data', $this->relationship);
  }
}
