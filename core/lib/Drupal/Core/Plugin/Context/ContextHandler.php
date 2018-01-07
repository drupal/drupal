<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Exception\ContextException;
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
      return $definition->isSatisfiedBy($context);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function applyContextMapping(ContextAwarePluginInterface $plugin, $contexts, $mappings = []) {
    /** @var $contexts \Drupal\Core\Plugin\Context\ContextInterface[] */
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
          $plugin->setContextValue($plugin_context_id, $contexts[$context_id]->getContextData());
        }
        elseif ($plugin_context_definition->isRequired()) {
          // Collect required contexts that exist but are missing a value.
          $missing_value[] = $plugin_context_id;
        }
      }
      elseif ($plugin_context_definition->isRequired()) {
        // Collect required contexts that are missing.
        $missing_value[] = $plugin_context_id;
      }
      else {
        // Ignore mappings for optional missing context.
        unset($mappings[$plugin_context_id]);
      }
    }

    // If there are any required contexts without a value, throw an exception.
    if ($missing_value) {
      throw new ContextException(sprintf('Required contexts without a value: %s.', implode(', ', $missing_value)));
    }

    // If there are any mappings that were not satisfied, throw an exception.
    if (!empty($mappings)) {
      throw new ContextException('Assigned contexts were not satisfied: ' . implode(',', array_keys($mappings)));
    }
  }

}
