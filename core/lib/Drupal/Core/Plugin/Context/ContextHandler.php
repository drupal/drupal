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
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Provides methods to handle sets of contexts.
 */
class ContextHandler implements ContextHandlerInterface {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Constructs a new ContextHandler.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data
   *   The typed data manager.
   */
  public function __construct(TypedDataManager $typed_data) {
    $this->typedDataManager = $typed_data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterPluginDefinitionsByContexts(array $contexts, array $definitions) {
    return array_filter($definitions, function ($plugin_definition) use ($contexts) {
      // If this plugin doesn't need any context, it is available to use.
      if (!isset($plugin_definition['context'])) {
        return TRUE;
      }

      // Build an array of requirements out of the contexts specified by the
      // plugin definition.
      $requirements = array();
      /** @var $plugin_context \Drupal\Core\Plugin\Context\ContextDefinitionInterface */
      foreach ($plugin_definition['context'] as $context_id => $plugin_context) {
        $definition = $this->typedDataManager->getDefinition($plugin_context->getDataType());
        $definition['type'] = $plugin_context->getDataType();

        // If the plugin specifies additional constraints, add them to the
        // constraints defined by the plugin type.
        if ($plugin_constraints = $plugin_context->getConstraints()) {
          // Ensure the array exists before adding in constraints.
          if (!isset($definition['constraints'])) {
            $definition['constraints'] = array();
          }

          $definition['constraints'] += $plugin_constraints;
        }

        // Assume the requirement is required if unspecified.
        if (!isset($definition['required'])) {
          $definition['required'] = TRUE;
        }

        // @todo Use context definition objects after
        //   https://drupal.org/node/2281635.
        $requirements[$context_id] = new DataDefinition($definition);
      }

      // Check the set of contexts against the requirements.
      return $this->checkRequirements($contexts, $requirements);
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
  public function getMatchingContexts(array $contexts, DataDefinitionInterface $definition) {
    return array_filter($contexts, function (ContextInterface $context) use ($definition) {
      $context_definition = $context->getContextDefinition()->getDataDefinition();

      // If the data types do not match, this context is invalid.
      if ($definition->getDataType() != $context_definition->getDataType()) {
        return FALSE;
      }

      // If any constraint does not match, this context is invalid.
      // @todo This is too restrictive, consider only relying on data types.
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
