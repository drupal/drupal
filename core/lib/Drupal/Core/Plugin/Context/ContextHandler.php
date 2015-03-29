<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\ContextHandler.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides methods to handle sets of contexts.
 */
class ContextHandler implements ContextHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function filterPluginDefinitionsByContexts(array $contexts, array $definitions) {
    return array_filter($definitions, function ($plugin_definition) use ($contexts) {
      // If this plugin doesn't need any context, it is available to use.
      if (!isset($plugin_definition['context'])) {
        return TRUE;
      }

      // Check the set of contexts against the requirements.
      return $this->checkRequirements($contexts, $plugin_definition['context']);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements(array $contexts, array $requirements) {
    foreach ($requirements as $requirement) {
      if ($requirement->isRequired() && !$this->getMatchingContexts($contexts, $requirement)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchingContexts(array $contexts, ContextDefinitionInterface $definition) {
    return array_filter($contexts, function (ContextInterface $context) use ($definition) {
      $context_definition = $context->getContextDefinition();

      // If the data types do not match, this context is invalid unless the
      // expected data type is any, which means all data types are supported.
      if ($definition->getDataType() != 'any' && $definition->getDataType() != $context_definition->getDataType()) {
        return FALSE;
      }

      // If any constraint does not match, this context is invalid.
      foreach ($definition->getConstraints() as $constraint_name => $constraint) {
        if ($context_definition->getConstraint($constraint_name) != $constraint) {
          return FALSE;
        }
      }

      // All contexts with matching data type and contexts are valid.
      return TRUE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function applyContextMapping(ContextAwarePluginInterface $plugin, $contexts, $mappings = array()) {
    $mappings += $plugin->getContextMapping();
    // Loop through each of the expected contexts.
    foreach (array_keys($plugin->getContextDefinitions()) as $plugin_context_id) {
      // If this context was given a specific name, use that.
      $context_id = isset($mappings[$plugin_context_id]) ? $mappings[$plugin_context_id] : $plugin_context_id;
      if (!empty($contexts[$context_id])) {
        // This assignment has been used, remove it.
        unset($mappings[$plugin_context_id]);
        $plugin->setContextValue($plugin_context_id, $contexts[$context_id]->getContextValue());
      }
    }

    // If there are any mappings that were not satisfied, throw an exception.
    if (!empty($mappings)) {
      throw new ContextException(SafeMarkup::format('Assigned contexts were not satisfied: @mappings', ['@mappings' => implode(',', array_keys($mappings))]));
    }
  }

}
