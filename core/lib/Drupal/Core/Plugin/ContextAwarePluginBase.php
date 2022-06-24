<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ContextAwarePluginBase as ComponentContextAwarePluginBase;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataTrait;

@trigger_error(__NAMESPACE__ . '\ContextAwarePluginBase is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Plugin\ContextAwarePluginTrait instead. See https://www.drupal.org/node/3120980', E_USER_DEPRECATED);

/**
 * Base class for plugins that are context aware.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Core\Plugin\ContextAwarePluginTrait instead.
 *
 * @see https://www.drupal.org/node/3120980
 */
abstract class ContextAwarePluginBase extends ComponentContextAwarePluginBase implements ContextAwarePluginInterface, CacheableDependencyInterface {

  use ContextAwarePluginTrait;
  use TypedDataTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  protected function createContextFromConfiguration(array $context_configuration) {
    // This method is overridden so that it will use
    // \Drupal\Core\Plugin\Context\Context instead.
    $contexts = [];
    foreach ($context_configuration as $key => $value) {
      $context_definition = $this->getContextDefinition($key);
      $contexts[$key] = new Context($context_definition, $value);
    }
    return $contexts;
  }

}
