<?php

declare(strict_types = 1);

namespace Drupal\Core\Plugin\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PluginExists constraint.
 */
class PluginExistsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $plugin_id, Constraint $constraint) {
    assert($constraint instanceof PluginExistsConstraint);

    if ($plugin_id === NULL) {
      return;
    }

    $definition = $constraint->pluginManager->getDefinition($plugin_id, FALSE);
    // Some plugin managers provide fallbacks. In most cases, the use of a
    // fallback plugin ID suggests that the given plugin ID is invalid in some
    // way, so by default, we don't consider fallback plugin IDs as valid,
    // although that can be overridden by the `allowFallback` option if needed.
    if ($constraint->pluginManager instanceof FallbackPluginManagerInterface && $constraint->allowFallback) {
      $fallback_plugin_id = $constraint->pluginManager->getFallbackPluginId($plugin_id);
      $definition = $constraint->pluginManager->getDefinition($fallback_plugin_id, FALSE);
    }

    if (empty($definition)) {
      $this->context->addViolation($constraint->unknownPluginMessage, [
        '@plugin_id' => $plugin_id,
      ]);
      return;
    }

    // If we don't need to validate the plugin class's interface, we're done.
    if (empty($constraint->interface)) {
      return;
    }

    if (!is_a(DefaultFactory::getPluginClass($plugin_id, $definition), $constraint->interface, TRUE)) {
      $this->context->addViolation($constraint->invalidInterfaceMessage, [
        '@plugin_id' => $plugin_id,
        '@interface' => $constraint->interface,
      ]);
    }
  }

}
