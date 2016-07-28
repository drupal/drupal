<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\PluginAwareInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;

/**
 * Provides form discovery capabilities for plugins.
 */
class PluginFormFactory implements PluginFormFactoryInterface {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * PluginFormFactory constructor.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(ClassResolverInterface $class_resolver) {
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance(PluginWithFormsInterface $plugin, $operation, $fallback_operation = NULL) {
    if (!$plugin->hasFormClass($operation)) {
      // Use the default form class if no form is specified for this operation.
      if ($fallback_operation && $plugin->hasFormClass($fallback_operation)) {
        $operation = $fallback_operation;
      }
      else {
        throw new InvalidPluginDefinitionException($plugin->getPluginId(), sprintf('The "%s" plugin did not specify a "%s" form class', $plugin->getPluginId(), $operation));
      }
    }

    $form_class = $plugin->getFormClass($operation);

    // If the form specified is the plugin itself, use it directly.
    if (ltrim(get_class($plugin), '\\') === ltrim($form_class, '\\')) {
      $form_object = $plugin;
    }
    else {
      $form_object = $this->classResolver->getInstanceFromDefinition($form_class);
    }

    // Ensure the resulting object is a plugin form.
    if (!$form_object instanceof PluginFormInterface) {
      throw new InvalidPluginDefinitionException($plugin->getPluginId(), sprintf('The "%s" plugin did not specify a valid "%s" form class, must implement \Drupal\Core\Plugin\PluginFormInterface', $plugin->getPluginId(), $operation));
    }

    if ($form_object instanceof PluginAwareInterface) {
      $form_object->setPlugin($plugin);
    }

    return $form_object;
  }

}
