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
 *     "node" = {
 *       "type" = "entity",
 *       "constraints" = {
 *         "EntityType" = "node"
 *       }
 *     }
 *   }
 * )
 */
class NodeType extends ConditionPluginBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    $options = array();
    foreach (node_type_get_types() as $type) {
      $options[$type->type] = $type->name;
    }
    $form['bundles'] = array(
      '#title' => t('Node types'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => isset($this->configuration['bundles']) ? $this->configuration['bundles'] : array(),
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['bundles'] as $bundle) {
      if (!in_array($bundle, array_keys(node_type_get_types()))) {
        form_set_error('bundles', $form_state, t('You have chosen an invalid node bundle, please check your selection and try again.'));
      }
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configuration['bundles'] = $form_state['values']['bundles'];
    parent::submitForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Executable\ExecutableInterface::summary().
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return t('The node bundle is @bundles or @last', array('@bundles' => $bundles, '@last' => $last));
    }
    $bundle = $this->configuration['bundles'][0];
    return t('The node bundle is @bundle', array('@bundle' => $bundle));
  }

  /**
   * Implements \Drupal\condition\ConditionInterface::evaluate().
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    return in_array($node->getType(), $this->configuration['bundles']);
  }

}
