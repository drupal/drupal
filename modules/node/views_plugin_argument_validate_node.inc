<?php

/**
 * @file
 * Contains the 'node' argument validator plugin.
 */

/**
 * Validate whether an argument is an acceptable node.
 */
class views_plugin_argument_validate_node extends views_plugin_argument_validate {
  function option_definition() {
    $options = parent::option_definition();
    $options['types'] = array('default' => array());
    $options['access'] = array('default' => FALSE, 'bool' => TRUE);
    $options['access_op'] = array('default' => 'view');
    $options['nid_type'] = array('default' => 'nid');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $types = node_type_get_types();
    $options = array();
    foreach ($types as $type => $info) {
      $options[$type] = check_plain(t($info->name));
    }

    $form['types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Content types'),
      '#options' => $options,
      '#default_value' => $this->options['types'],
      '#description' => t('Choose one or more content types to validate with.'),
    );

    $form['access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Validate user has access to the content'),
      '#default_value' => $this->options['access'],
    );
    $form['access_op'] = array(
      '#type' => 'radios',
      '#title' => t('Access operation to check'),
      '#options' => array('view' => t('View'), 'update' => t('Edit'), 'delete' => t('Delete')),
      '#default_value' => $this->options['access_op'],
      '#dependency' => array('edit-options-validate-options-node-access' => array(TRUE)),
    );

    $form['nid_type'] = array(
      '#type' => 'select',
      '#title' => t('Filter value format'),
      '#options' => array(
        'nid' => t('Node ID'),
        'nids' => t('Node IDs separated by , or +'),
      ),
      '#default_value' => $this->options['nid_type'],
    );
  }

  function options_submit(&$form, &$form_state, &$options = array()) {
    // filter trash out of the options so we don't store giant unnecessary arrays
    $options['types'] = array_filter($options['types']);
  }

  function convert_options(&$options) {
    if (!isset($options['types']) && !empty($this->argument->options['validate_argument_node_type'])) {
      $options['types'] = isset($this->argument->options['validate_argument_node_type']) ? $this->argument->options['validate_argument_node_type'] : array();
      $options['access'] = !empty($this->argument->options['validate_argument_node_access']);
      $options['access_op'] = isset($this->argument->options['validate_argument_node_access_op']) ? $this->argument->options['validate_argument_node_access_op'] : 'view';
      $options['nid_type'] = isset($this->argument->options['validate_argument_nid_type']) ? $this->argument->options['validate_argument_nid_type'] : array();
    }
  }

  function validate_argument($argument) {
    $types = $this->options['types'];

    switch ($this->options['nid_type']) {
      case 'nid':
        if (!is_numeric($argument)) {
          return FALSE;
        }
        $node = node_load($argument);
        if (!$node) {
          return FALSE;
        }

        if (!empty($this->options['access'])) {
          if (!node_access($this->options['access_op'], $node)) {
            return FALSE;
          }
        }

        // Save the title() handlers some work.
        $this->argument->validated_title = check_plain($node->title);

        if (empty($types)) {
          return TRUE;
        }

        return isset($types[$node->type]);
      break;
      case 'nids':
        $nids = new stdClass();
        $nids->value = array($argument);
        $nids = views_break_phrase($argument, $nids);
        if ($nids->value == array(-1)) {
          return FALSE;
        }

        $test = drupal_map_assoc($nids->value);
        $titles = array();

        $result = db_query("SELECT * FROM {node} WHERE nid IN (:nids)", array(':nids' =>  $nids->value));
        foreach ($result as $node) {
          if ($types && empty($types[$node->type])) {
            return FALSE;
          }

          if (!empty($this->options['access'])) {
            if (!node_access($this->options['access_op'], $node)) {
              return FALSE;
            }
          }

          $titles[] = check_plain($node->title);
          unset($test[$node->nid]);
        }

        $this->argument->validated_title = implode($nids->operator == 'or' ? ' + ' : ', ', $titles);
        // If this is not empty, we did not find a nid.
        return empty($test);
    }
  }
}
