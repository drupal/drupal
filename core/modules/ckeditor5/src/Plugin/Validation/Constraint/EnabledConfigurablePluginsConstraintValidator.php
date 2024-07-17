<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Enabled configurable plugin settings validator.
 *
 * @internal
 */
class EnabledConfigurablePluginsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;
  use TextEditorObjectDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($settings, Constraint $constraint): void {
    if (!$constraint instanceof EnabledConfigurablePluginsConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\EnabledConfigurablePluginsConstraint');
    }

    $configurable_enabled_definitions = $this->getConfigurableEnabledDefinitions();
    try {
      $plugin_settings = $this->context->getRoot()->get('settings.plugins')->getValue();
    }
    catch (\InvalidArgumentException) {
      $plugin_settings = [];
    }

    foreach ($configurable_enabled_definitions as $id => $definition) {
      // Create a fresh instance of this CKEditor 5 plugin, not tied to a text
      // editor configuration entity.
      $plugin = $this->pluginManager->getPlugin($id, NULL);
      // If this plugin is configurable but it has empty default configuration,
      // that means the configuration must be stored out of band.
      // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image
      // @see editor_image_upload_settings_form()
      $default_configuration = $plugin->defaultConfiguration();
      if ($default_configuration === []) {
        continue;
      }

      if (!isset($plugin_settings[$id]) || empty($plugin_settings[$id])) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%plugin_label', (string) $definition->label())
          ->setParameter('%plugin_id', $id)
          ->atPath("plugins.$id")
          ->addViolation();
      }
    }
  }

  /**
   * Gets all configurable CKEditor 5 plugin definitions that are enabled.
   *
   * @return \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[]
   *   An array of enabled configurable CKEditor 5 plugin definitions.
   */
  private function getConfigurableEnabledDefinitions(): array {
    $text_editor = $this->createTextEditorObjectFromContext();
    $enabled_definitions = $this->pluginManager->getEnabledDefinitions($text_editor);
    $configurable_enabled_definitions = array_filter($enabled_definitions, function (CKEditor5PluginDefinition $definition): bool {
      return $definition->isConfigurable();
    });

    return $configurable_enabled_definitions;
  }

}
