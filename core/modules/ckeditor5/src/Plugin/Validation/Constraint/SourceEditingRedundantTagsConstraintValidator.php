<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
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
    $enabled_plugin_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($enabled_plugins)));
    $disabled_plugin_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($disabled_plugins)));

    // The single tag for which source editing is enabled, which we are checking
    // now.
    $source_enabled_tags = HTMLRestrictions::fromString($value);
    // @todo Remove this early return in
    //   https://www.drupal.org/project/drupal/issues/2820364. It is only
    //   necessary because CKEditor5ElementConstraintValidator does not run
    //   before this, which means that this validator cannot assume it receives
    //   valid values.
    if ($source_enabled_tags->isEmpty() || count($source_enabled_tags->getAllowedElements()) > 1) {
      return;
    }
    // This validation constraint currently only validates tags, not attributes;
    // so if all attributes are allowed (TRUE) or some attributes are allowed
    // (an array), return early. Only proceed when no attributes are allowed
    // (FALSE).
    // @todo Support attributes and attribute values in
    //   https://www.drupal.org/project/drupal/issues/3260857
    $tags = array_keys($source_enabled_tags->getAllowedElements());
    if ($source_enabled_tags->getAllowedElements()[reset($tags)] !== FALSE) {
      return;
    }

    $enabled_plugin_overlap = $enabled_plugin_tags->intersect($source_enabled_tags);
    $disabled_plugin_overlap = $disabled_plugin_tags->intersect($source_enabled_tags);
    foreach ([$enabled_plugin_overlap, $disabled_plugin_overlap] as $overlap) {
      $checking_enabled = $overlap === $enabled_plugin_overlap;
      if (!$overlap->isEmpty()) {
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
   * @param \Drupal\ckeditor5\HTMLRestrictions $overlap
   *   An array of overlapping tags.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[] $plugin_definitions
   *   An array of plugin definitions where overlap was found.
   *
   * @return string
   *   A list of plugins that provide the overlapping tags.
   */
  private function pluginsSupplyingTagsMessage(HTMLRestrictions $overlap, array $plugin_definitions): string {
    $message_array = [];
    $message_string = '';
    foreach ($plugin_definitions as $plugin_id => $definition) {
      if ($definition->hasElements()) {
        $plugin_capabilities = HTMLRestrictions::fromString(implode(' ', $definition->getElements()));
        foreach ($plugin_capabilities->intersect($overlap)->toCKEditor5ElementsArray() as $element) {
          $message_array[(string) $definition->label()][] = $element;
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
