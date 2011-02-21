<?php
// $Id: views_plugin_argument_validate_node.inc,v 1.2.4.3 2010/05/15 08:11:14 dereine Exp $
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
    $options['access'] = array('default' => FALSE);
    $options['nid_type'] = array('default' => 'nid');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $types = node_type_get_types();
    foreach ($types as $type => $info) {
      $options[$type] = check_plain(t($info->name));
    }

    $form['types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Types'),
      '#options' => $options,
      '#default_value' => $this->options['types'],
      '#description' => t('If you wish to validate for specific node types, check them; if none are checked, all nodes will pass.'),
    );

    $form['access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Validate user has access to the node'),
      '#default_value' => $this->options['access'],
    );

    $form['nid_type'] = array(
      '#type' => 'select',
      '#title' => t('Argument type'),
      '#options' => array(
        'nid' => t('Node ID'),
        'nids' => t('Node IDs separated by , or +'),
      ),
      '#default_value' => $this->options['nid_type'],
    );
  }

  function options_submit(&$form, &$form_state, &$options) {
    // filter trash out of the options so we don't store giant unnecessary arrays
    $options['types'] = array_filter($options['types']);
  }

  function convert_options(&$options) {
    if (!isset($options['types']) && !empty($this->argument->options['validate_argument_node_type'])) {
      $options['types'] = $this->argument->options['validate_argument_node_type'];
      $options['access'] = !empty($this->argument->options['validate_argument_node_access']);
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
          if (!node_access('view', $node)) {
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
        $nids = views_nid_type($argument, $nids);
        if ($nids->value == -1) {
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
            if (!node_access('view', $node)) {
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

