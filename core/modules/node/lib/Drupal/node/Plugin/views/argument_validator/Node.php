<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument_validator\Node.
 */

namespace Drupal\node\Plugin\views\argument_validator;

use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Validate whether an argument is an acceptable node.
 *
 * @ViewsArgumentValidator(
 *   id = "node",
 *   title = @Translation("Content")
 * )
 */
class Node extends ArgumentValidatorPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['types'] = array('default' => array());
    $options['access'] = array('default' => FALSE, 'bool' => TRUE);
    $options['access_op'] = array('default' => 'view');
    $options['nid_type'] = array('default' => 'nid');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
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
      '#states' => array(
        'visible' => array(
          ':input[name="options[validate][options][node][access]"]' => array('checked' => TRUE),
        ),
      ),
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

  public function submitOptionsForm(&$form, &$form_state, &$options = array()) {
    // filter trash out of the options so we don't store giant unnecessary arrays
    $options['types'] = array_filter($options['types']);
  }

  public function validateArgument($argument) {
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
          if (!$node->access($this->options['access_op'])) {
            return FALSE;
          }
        }

        // Save the title() handlers some work.
        $this->argument->validated_title = check_plain($node->label());

        if (empty($types)) {
          return TRUE;
        }

        return isset($types[$node->getType()]);

      case 'nids':
        $nids = new stdClass();
        $nids->value = array($argument);
        $nids = $this->breakPhrase($argument, $nids);
        if ($nids->value == array(-1)) {
          return FALSE;
        }

        $test = array_combine($nids->value, $nids->value);
        $titles = array();

        $nodes = node_load_multiple($nids->value);
        foreach ($nodes as $node) {
          if ($types && empty($types[$node->getType()])) {
            return FALSE;
          }

          if (!empty($this->options['access'])) {
            if (!$node->access($this->options['access_op'])) {
              return FALSE;
            }
          }

          $titles[] = check_plain($node->label());
          unset($test[$node->id()]);
        }

        $this->argument->validated_title = implode($nids->operator == 'or' ? ' + ' : ', ', $titles);
        // If this is not empty, we did not find a nid.
        return empty($test);
    }
  }

}
