<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\ContextHandler.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Utility\String;

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
    if ($plugin instanceof ConfigurablePluginInterface) {
      $configuration = $plugin->getConfiguration();
      if (isset($configuration['context_mapping'])) {
        $mappings += array_flip($configuration['context_mapping']);
      }
    }
    $plugin_contexts = $plugin->getContextDefinitions();
    // Loop through each context and set it on the plugin if it matches one of
    // the contexts expected by the plugin.
    foreach ($contexts as $name => $context) {
      // If this context was given a specific name, use that.
      $assigned_name = isset($mappings[$name]) ? $mappings[$name] : $name;
      if (isset($plugin_contexts[$assigned_name])) {
        // This assignment has been used, remove it.
        unset($mappings[$name]);
        $plugin->setContextValue($assigned_name, $context->getContextValue());
      }
    }

    // If there are any mappings that were not satisfied, throw an exception.
    if (!empty($mappings)) {
      throw new ContextException(String::format('Assigned contexts were not satisfied: @mappings', array('@mappings' => implode(',', $mappings))));
    }
  }

}
