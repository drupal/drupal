<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictionsUtilities;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Ensures tags already available via plugin are not be added to Source Editing.
 *
 * @internal
 */
class SourceEditingRedundantTagsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;
  use TextEditorObjectDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof SourceEditingRedundantTagsConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\SourceEditingRedundantTagsConstraint');
    }
    if (empty($value)) {
      return;
    }

    $text_editor = $this->createTextEditorObjectFromContext();
    $enabled_plugins = $this->pluginManager->getEnabledDefinitions($text_editor);
    $disabled_plugins = array_diff_key($this->pluginManager->getDefinitions(), $enabled_plugins);
    unset($enabled_plugins['ckeditor5_sourceEditing']);

    // An array of tags enabled by every plugin other than Source Editing.
    $enabled_plugin_tags = $this->pluginManager->getProvidedElements(array_keys($enabled_plugins));
    $disabled_plugin_tags = $this->pluginManager->getProvidedElements(array_keys($disabled_plugins));

    // An array of just the tags enabled by Source Editing.
    $source_enabled_tags = HTMLRestrictionsUtilities::allowedElementsStringToHtmlFilterArray($value);
    $enabled_plugin_overlap = array_intersect_key($enabled_plugin_tags, $source_enabled_tags);
    $disabled_plugin_overlap = array_intersect_key($disabled_plugin_tags, $source_enabled_tags);

    foreach ([$enabled_plugin_overlap, $disabled_plugin_overlap] as &$overlap) {
      $checking_enabled = $overlap === $enabled_plugin_overlap;
      if (!empty($overlap)) {
        foreach ($overlap as $overlapping_tag => $overlapping_config) {
          if (is_array($source_enabled_tags[$overlapping_tag])) {
            unset($overlap[$overlapping_tag]);
          }
        }
      }
      if (!empty($overlap)) {
        $plugins_to_check_against = $checking_enabled ? $enabled_plugins : $disabled_plugins;
        $tags_plugin_report = $this->pluginsSupplyingTagsMessage($overlap, $plugins_to_check_against);
        $message = $checking_enabled ? $constraint->enabledPluginsMessage : $constraint->availablePluginsMessage;
        $this->context->buildViolation($message)
          ->setParameter('%overlapping_tags', $tags_plugin_report)
          ->addViolation();
      }
    }
  }

  /**
   * Creates a message listing plugins and the overlapping tags they provide.
   *
   * @param array $tags
   *   An array of overlapping tags.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[] $plugin_definitions
   *   An array of plugin definitions where overlap was found.
   *
   * @return string
   *   A list of plugins that provide the overlapping tags.
   */
  private function pluginsSupplyingTagsMessage(array $tags, array $plugin_definitions): string {
    $message_array = [];
    $message_string = '';
    foreach ($plugin_definitions as $plugin_id => $definition) {
      if ($definition->hasElements()) {
        $elements_array = HTMLRestrictionsUtilities::allowedElementsStringToHtmlFilterArray(implode('', $definition->getElements()));
        foreach ($elements_array as $tag_name => $tag_config) {
          if (isset($tags[$tag_name])) {
            $message_array[(string) $definition->label()][] = "<$tag_name>";
          }
        }
      }
    }
    foreach ($message_array as $plugin_label => $tag_list) {
      $tags_string = implode(', ', $tag_list);
      $message_string .= "$plugin_label ($tags_string), ";
    }

    return trim($message_string, ' ,');
  }

}
