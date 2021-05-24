<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides methods to handle sets of contexts.
 */
class ContextHandler implements ContextHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function filterPluginDefinitionsByContexts(array $contexts, array $definitions) {
    $checked_requirements = [];
    return array_filter($definitions, function ($plugin_definition) use ($contexts, &$checked_requirements) {
      $context_definitions = $this->getContextDefinitions($plugin_definition);
      if ($context_definitions) {
        // Generate a unique key for the current context definitions. This will
        // allow calling checkRequirements() once for all plugins that have the
        // same context definitions.
        $context_definitions_key = hash('sha256', serialize($context_definitions));
        if (!isset($checked_requirements[$context_definitions_key])) {
          // Check the set of contexts against the requirements.
          $checked_requirements[$context_definitions_key] = $this->checkRequirements($contexts, $context_definitions);
        }
        return $checked_requirements[$context_definitions_key];
      }
      // If this plugin doesn't need any context, it is available to use.
      return TRUE;
    });
  }

  /**
   * Returns the context definitions associated with a plugin definition.
   *
   * @param array|\Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface[]|null
   *   The context definitions, or NULL if the plugin definition does not
   *   support contexts.
   */
  protected function getContextDefinitions($plugin_definition) {
    if ($plugin_definition instanceof ContextAwarePluginDefinitionInterface) {
      return $plugin_definition->getContextDefinitions();
    }
    if (is_array($plugin_definition) && isset($plugin_definition['context_definitions'])) {
      return $plugin_definition['context_definitions'];
    }
    return NULL;
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
      return $definition->isSatisfiedBy($context);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function applyContextMapping(ContextAwarePluginInterface $plugin, $contexts, $mappings = []) {
    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $mappings += $plugin->getContextMapping();
    // Loop through each of the expected contexts.

    $missing_value = [];

    foreach ($plugin->getContextDefinitions() as $plugin_context_id => $plugin_context_definition) {
      // If this context was given a specific name, use that.
      $context_id = isset($mappings[$plugin_context_id]) ? $mappings[$plugin_context_id] : $plugin_context_id;
      if (!empty($contexts[$context_id])) {
        // This assignment has been used, remove it.
        unset($mappings[$plugin_context_id]);

        // Plugins have their on context objects, only the value is applied.
        // They also need to know about the cacheability metadata of where that
        // value is coming from, so pass them through to those objects.
        $plugin_context = $plugin->getContext($plugin_context_id);
        if ($plugin_context instanceof ContextInterface && $contexts[$context_id] instanceof CacheableDependencyInterface) {
          $plugin_context->addCacheableDependency($contexts[$context_id]);
        }

        // Pass the value to the plugin if there is one.
        if ($contexts[$context_id]->hasContextValue()) {
          $plugin->setContext($plugin_context_id, $contexts[$context_id]);
        }
        elseif ($plugin_context_definition->isRequired()) {
          // Collect required contexts that exist but are missing a value.
          $missing_value[] = $plugin_context_id;
        }

        // Proceed to the next definition.
        continue;
      }

      try {
        $context = $plugin->getContext($context_id);
      }
      catch (ContextException $e) {
        $context = NULL;
      }
      // @todo Remove in https://www.drupal.org/project/drupal/issues/3046342.
      catch (PluginException $e) {
        $context = NULL;
      }

      if ($context && $context->hasContextValue()) {
        // Ignore mappings if the plugin has a value for a missing context.
        unset($mappings[$plugin_context_id]);
        continue;
      }

      if ($plugin_context_definition->isRequired()) {
        // Collect required contexts that are missing.
        $missing_value[] = $plugin_context_id;
        continue;
      }

      // Ignore mappings for optional missing context.
      unset($mappings[$plugin_context_id]);
    }

    // If there are any mappings that were not satisfied, throw an exception.
    // This is a more severe problem than missing values, so check and throw
    // this first.
    if (!empty($mappings)) {
      throw new ContextException('Assigned contexts were not satisfied: ' . implode(',', array_keys($mappings)));
    }

    // If there are any required contexts without a value, throw an exception.
    if ($missing_value) {
      throw new MissingValueContextException($missing_value);
    }
  }

}
