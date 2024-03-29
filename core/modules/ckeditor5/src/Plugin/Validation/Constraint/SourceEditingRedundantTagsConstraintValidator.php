<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

// cspell:ignore enableable

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
  use StringTranslationTrait;
  use TextEditorObjectDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($value, Constraint $constraint): void {
    if (!$constraint instanceof SourceEditingRedundantTagsConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\SourceEditingRedundantTagsConstraint');
    }
    if (empty($value)) {
      return;
    }

    $text_editor = $this->createTextEditorObjectFromContext();

    $other_enabled_plugins = $this->getOtherEnabledPlugins($text_editor, 'ckeditor5_sourceEditing');
    $enableable_disabled_plugins = $this->getEnableableDisabledPlugins($text_editor);

    // An array of tags enabled by every plugin other than Source Editing.
    $enabled_plugin_elements = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($other_enabled_plugins), $text_editor, FALSE));
    $enabled_plugin_elements_optional = (new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($other_enabled_plugins))))
      ->diff($enabled_plugin_elements);
    $disabled_plugin_elements = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($enableable_disabled_plugins), $text_editor, FALSE));
    $enabled_plugin_plain_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($other_enabled_plugins), $text_editor, FALSE, TRUE));
    $disabled_plugin_plain_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($enableable_disabled_plugins), $text_editor, FALSE, TRUE));

    // The single element for which source editing is enabled, which we are
    // checking now.
    $source_enabled_element = HTMLRestrictions::fromString($value);
    // Test for empty allowed elements with resolved wildcards since, for the
    // purposes of this validator, HTML restrictions containing only wildcards
    // should be considered empty.
    // @todo Remove this early return in
    //   https://www.drupal.org/project/drupal/issues/2820364. It is only
    //   necessary because CKEditor5ElementConstraintValidator does not run
    //   before this, which means that this validator cannot assume it receives
    //   valid values.
    if (count($source_enabled_element->getAllowedElements()) !== 1) {
      return;
    }

    $enabled_plugin_overlap = $enabled_plugin_elements->intersect($source_enabled_element);
    $enabled_plugin_optional_overlap = $enabled_plugin_elements_optional->intersect($source_enabled_element);
    $disabled_plugin_overlap = $disabled_plugin_elements
      // Merge the enabled plugins' elements, to allow wildcards to be resolved.
      ->merge($enabled_plugin_elements)
      // Compute the overlap.
      ->intersect($source_enabled_element)
      // Exclude the enabled plugin tags from the overlap; we merged these
      // previously to be able to resolve wildcards.
      ->diff($enabled_plugin_overlap);
    foreach ([$enabled_plugin_overlap, $enabled_plugin_optional_overlap, $disabled_plugin_overlap] as $overlap) {
      $checking_enabled = $overlap === $enabled_plugin_overlap || $overlap === $enabled_plugin_optional_overlap;
      if (!$overlap->allowsNothing()) {
        $plugins_to_check_against = $checking_enabled ? $other_enabled_plugins : $enableable_disabled_plugins;
        $plain_tags_to_check_against = $checking_enabled ? $enabled_plugin_plain_tags : $disabled_plugin_plain_tags;
        $tags_plugin_report = $this->pluginsSupplyingTagsMessage($overlap, $plugins_to_check_against, $enabled_plugin_elements);
        $message = match($overlap) {
          $enabled_plugin_overlap => $constraint->enabledPluginsMessage,
          $enabled_plugin_optional_overlap => $constraint->enabledPluginsOptionalMessage,
          $disabled_plugin_overlap => $constraint->availablePluginsMessage,
        };

        // Determine which element type is relevant for the violation message.
        assert(count($overlap->getAllowedElements(FALSE)) === 1);
        $overlap_tag = array_keys($overlap->getAllowedElements(FALSE))[0];
        $is_attr_overlap = self::tagHasAttributeRestrictions($overlap, $overlap_tag);

        // If one or more attributes (and all of the allowed attribute values)
        // of the HTML elements being configured to be edited via the Source
        // Editing plugin is supported by a CKEditor 5 plugin, complain. But if
        // an attribute overlap is detected due to a wildcard attribute, then do
        // not generate a violation message.
        // For example:
        // - value `<ol start foo>` triggers a violation because `<ol start>` is
        //   supported by the `ckeditor5_list` plugin
        // - value `<img data-*>` does NOT trigger a violation because only
        //   concrete `data-`-attributes are supported by the
        //   `ckeditor5_imageUpload`, `ckeditor5_imageCaption` and
        //   `ckeditor5_imageAlign` plugins
        if ($is_attr_overlap && $source_enabled_element->diff($overlap)->getAllowedElements(FALSE) == $source_enabled_element->getAllowedElements(FALSE)) {
          continue;
        }

        // If there is overlap, but the plain tag is not supported in the
        // overlap, exit this iteration without generating a violation message.
        // Essentially when assessing a particular value (for example `<span>`),
        // CKEditor 5 plugins supporting only the creation of attributes on this
        // tag (`<span lang>`) and not supporting the creation of this plain tag
        // (`<span>` explicitly listed in their elements) can trigger a
        // violation.
        if (!$is_attr_overlap) {
          $value_is_plain_tag_only = !self::tagHasAttributeRestrictions($source_enabled_element, $overlap_tag);
          // When the configured value is a plain tag (`<tag>`): do not generate
          // a violation message if this tag cannot be created by any CKEditor 5
          // plugin.
          if ($value_is_plain_tag_only && $overlap->intersect($plain_tags_to_check_against)->allowsNothing()) {
            continue;
          }
          // When the configured value is not a plain tag (so the value has the
          // shape `<tag attr>`, not `<tag>`): do not generate a violation
          // message if the tag can already be created by another CKEditor 5
          // plugin: this is just adding the ability to set more attributes.
          // Note: this does not check whether the plain tag can indeed be
          // created, validating that is out of scope for this validator.
          // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator::checkAllHtmlTagsAreCreatable()
          if (!$value_is_plain_tag_only) {
            continue;
          }
        }

        // If we reach this, it means the entirety (so not just the tag but also
        // the attributes, and not just some of the attribute values, but all of
        // them) of the HTML elements being configured to be edited via the
        // Source Editing plugin's 'allowed_tags' configuration is supported by
        // a CKEditor 5 plugin. This earns a violation.
        $this->context->buildViolation($message)
          ->setParameter('@element_type', $is_attr_overlap
            ? $this->t('attribute')
            : $this->t('tag')
          )
          ->setParameter('%overlapping_tags', $tags_plugin_report)
          ->addViolation();
      }
    }
  }

  /**
   * Inspects whether the given tag has attribute restrictions.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $r
   *   A set of HTML restrictions to inspect.
   * @param string $tag_name
   *   The tag to check for attribute restrictions in $r.
   *
   * @return bool
   *   TRUE if the given tag has attribute restrictions, FALSE otherwise.
   */
  private static function tagHasAttributeRestrictions(HTMLRestrictions $r, string $tag_name): bool {
    $all_elements = $r->getAllowedElements(FALSE);
    assert(isset($all_elements[$tag_name]));
    return is_array($r->getAllowedElements(FALSE)[$tag_name]);
  }

  /**
   * Creates a message listing plugins and the overlapping tags they provide.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $overlap
   *   An array of overlapping tags.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[] $plugin_definitions
   *   An array of plugin definitions where overlap was found.
   * @param \Drupal\ckeditor5\HTMLRestrictions $enabled_plugin_restrictions
   *   The set of HTML restrictions for all already enabled CKEditor 5 plugins.
   *
   * @return string
   *   A list of plugins that provide the overlapping tags.
   */
  private function pluginsSupplyingTagsMessage(HTMLRestrictions $overlap, array $plugin_definitions, HTMLRestrictions $enabled_plugin_restrictions): string {
    $message_array = [];
    $message_string = '';
    foreach ($plugin_definitions as $definition) {
      if ($definition->hasElements()) {
        $plugin_capabilities = HTMLRestrictions::fromString(implode(' ', $definition->getElements()));

        // If this plugin supports wildcards, resolve them.
        if (!$plugin_capabilities->getWildcardSubset()->allowsNothing()) {
          $plugin_capabilities = $plugin_capabilities
            // Resolve wildcards.
            ->merge($enabled_plugin_restrictions)
            ->diff($enabled_plugin_restrictions);
        }

        // Skip plugins that provide a subset, only mention the plugin that
        // actually provides the overlap.
        // For example: avoid listing the image alignment/captioning plugins
        // when matching `<img src>`; only lists the main image plugin.
        if (!$overlap->diff($plugin_capabilities)->allowsNothing()) {
          continue;
        }
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
