<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Condition\NodeType.
 */

namespace Drupal\node\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a 'Node Type' condition.
 *
 * @Condition(
 *   id = "node_type",
 *   label = @Translation("Node Bundle"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 *
 */
class NodeType extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $options = array();
    foreach (node_type_get_types() as $type) {
      $options[$type->type] = $type->name;
    }
    $form['bundles'] = array(
      '#title' => t('Node types'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['bundles'] = array_filter($form_state['values']['bundles']);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return t('The node bundle is @bundles or @last', array('@bundles' => $bundles, '@last' => $last));
    }
    $bundle = reset($this->configuration['bundles']);
    return t('The node bundle is @bundle', array('@bundle' => $bundle));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['bundles']) && !$this->isNegated()) {
      return TRUE;
    }
    $node = $this->getContextValue('node');
    return !empty($this->configuration['bundles'][$node->getType()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('bundles' => array()) + parent::defaultConfiguration();
  }

}
