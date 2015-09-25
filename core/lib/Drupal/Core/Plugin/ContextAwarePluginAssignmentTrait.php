<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait.
 */

namespace Drupal\Core\Plugin;

/**
 * Handles context assignments for context-aware plugins.
 */
trait ContextAwarePluginAssignmentTrait {

  /**
   * Ensures the t() method is available.
   *
   * @see \Drupal\Core\StringTranslation\StringTranslationTrait
   */
  abstract protected function t($string, array $args = array(), array $options = array());

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

  /**
   * Builds a form element for assigning a context to a given slot.
   *
   * @param \Drupal\Core\Plugin\ContextAwarePluginInterface $plugin
   *   The context-aware plugin.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts.
   *
   * @return array
   *   A form element for assigning context.
   */
  protected function addContextAssignmentElement(ContextAwarePluginInterface $plugin, array $contexts) {
    $element = [];
    foreach ($plugin->getContextDefinitions() as $context_slot => $definition) {
      $valid_contexts = $this->contextHandler()->getMatchingContexts($contexts, $definition);
      $options = [];
      foreach ($valid_contexts as $context_id => $context) {
        $element['#tree'] = TRUE;
        $options[$context_id] = $context->getContextDefinition()->getLabel();
        $element[$context_slot] = [
          '#type' => 'value',
          '#value' => $context_id,
        ];
      }

      if (count($options) > 1) {
        $assignments = $plugin->getContextMapping();
        $element[$context_slot] = [
          '#title' => $definition->getLabel() ?: $this->t('Select a @context value:', ['@context' => $context_slot]),
          '#type' => 'select',
          '#options' => $options,
          '#required' => $definition->isRequired(),
          '#default_value' => !empty($assignments[$context_slot]) ? $assignments[$context_slot] : '',
          '#description' => $definition->getDescription(),
        ];
      }
    }
    return $element;
  }

}
