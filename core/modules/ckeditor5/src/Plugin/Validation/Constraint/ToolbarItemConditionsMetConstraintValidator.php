<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Toolbar item conditions met constraint validator.
 *
 * @internal
 */
class ToolbarItemConditionsMetConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;
  use TextEditorObjectDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($toolbar_item, Constraint $constraint): void {
    if (!$constraint instanceof ToolbarItemConditionsMetConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ToolbarItemConditionsMetConstraint');
    }

    try {
      $definition = $this->findDefinitionForToolbarItem($toolbar_item);
    }
    catch (\OutOfBoundsException $e) {
      // No plugin definition found for this toolbar item. It's the
      // responsibility of another validation constraint to raise this problem.
      // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConstraint
      return;
    }

    // If there are no conditions, there is nothing to validate.
    if (!$definition->hasConditions()) {
      return;
    }

    $toolbar_item_label = $definition->getToolbarItems()[$toolbar_item]['label'];
    $text_editor = $this->createTextEditorObjectFromContext();

    $conditions = $definition->getConditions();
    foreach ($conditions as $condition_type => $required_value) {
      switch ($condition_type) {
        case 'toolbarItem':
          // Nothing to validate.
          break;

        case 'imageUploadStatus':
          $image_upload_settings = $text_editor->getImageUploadSettings();
          if (!isset($image_upload_settings['status']) || (bool) $image_upload_settings['status'] !== TRUE) {
            $this->context->buildViolation($constraint->imageUploadStatusRequiredMessage)
              ->setParameter('%toolbar_item', (string) $toolbar_item_label)
              ->setInvalidValue($toolbar_item)
              ->addViolation();
          }
          break;

        case 'filter':
          $filters = $text_editor->getFilterFormat()->filters();
          if (!$filters->has($required_value) || !$filters->get($required_value)->status) {
            $filter_label = $filters->has($required_value)
              ? $filters->get($required_value)->getLabel()
              : $required_value;
            $this->context->buildViolation($constraint->filterRequiredMessage)
              ->setParameter('%toolbar_item', (string) $toolbar_item_label)
              ->setParameter('%filter', (string) $filter_label)
              ->setInvalidValue($toolbar_item)
              ->addViolation();
          }
          break;

        case 'plugins':
          $enabled_definitions = $this->pluginManager->getEnabledDefinitions($text_editor);
          if (!array_key_exists($definition->id(), $enabled_definitions)) {
            $required_plugin_ids = $definition->getConditions()['plugins'];
            $missing_plugin_ids = array_diff($required_plugin_ids, array_keys($enabled_definitions));
            $all_plugins = $this->pluginManager->getDefinitions();
            $missing_plugin_labels = array_map(function (string $plugin_id) use ($all_plugins): TranslatableMarkup {
              return !array_key_exists($plugin_id, $all_plugins)
                ? $plugin_id
                : $all_plugins[$plugin_id]->label();
            }, $missing_plugin_ids);
            if (count($missing_plugin_ids) === 1) {
              $message = $constraint->singleMissingRequiredPluginMessage;
              $parameter = '%plugin';
            }
            else {
              $message = $constraint->multipleMissingRequiredPluginMessage;
              $parameter = '%plugins';
            }
            $this->context->buildViolation($message)
              ->setParameter('%toolbar_item', (string) $toolbar_item_label)
              ->setParameter($parameter, implode(', ', $missing_plugin_labels))
              ->setInvalidValue($toolbar_item)
              ->addViolation();
          }
          break;
      }
    }
  }

  /**
   * Searches for CKEditor 5 plugin that provides a given toolbar item.
   *
   * @param string $toolbar_item
   *   The toolbar item to be searched for within plugin definitions.
   *
   * @return \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition
   *   The corresponding plugin definition.
   *
   * @throws \OutOfBoundsException
   */
  protected function findDefinitionForToolbarItem(string $toolbar_item): CKEditor5PluginDefinition {
    $definitions = $this->pluginManager->getDefinitions();
    foreach ($definitions as $definition) {
      if (array_key_exists($toolbar_item, $definition->getToolbarItems())) {
        return $definition;
      }
    }

    // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConstraint
    throw new \OutOfBoundsException("Toolbar item '$toolbar_item' not found.");
  }

}
