<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
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
    // Only consider plugins that can be explicitly enabled by the user: plugins
    // that have a toolbar item and do not have conditions. Those are the only
    // plugins that are truly available for the site builder to enable without
    // other consequences.
    // In the future, we may choose to expand this, but it will require complex
    // infrastructure to generate messages that explain which of the conditions
    // are already fulfilled and which are not.
    $disabled_plugins = array_filter($disabled_plugins, function (CKEditor5PluginDefinition $definition) {
      return $definition->hasToolbarItems() && !$definition->hasConditions();
    });
    unset($enabled_plugins['ckeditor5_sourceEditing']);

    // An array of tags enabled by every plugin other than Source Editing.
    $enabled_plugin_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($enabled_plugins), $text_editor, FALSE));
    $disabled_plugin_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($disabled_plugins), $text_editor, FALSE));

    // The single tag for which source editing is enabled, which we are checking
    // now.
    $source_enabled_tags = HTMLRestrictions::fromString($value);
    // Test for empty allowed elements with resolved wildcards since, for the
    // purposes of this validator, HTML restrictions containing only wildcards
    // should be considered empty.
    // @todo Remove this early return in
    //   https://www.drupal.org/project/drupal/issues/2820364. It is only
    //   necessary because CKEditor5ElementConstraintValidator does not run
    //   before this, which means that this validator cannot assume it receives
    //   valid values.
    if (count($source_enabled_tags->getAllowedElements()) !== 1) {
      return;
    }

    $enabled_plugin_overlap = $enabled_plugin_tags->intersect($source_enabled_tags);
    $disabled_plugin_overlap = $disabled_plugin_tags
      // Merge the enabled plugin tags, to allow wildcards to be resolved.
      ->merge($enabled_plugin_tags)
      // Compute the overlap.
      ->intersect($source_enabled_tags)
      // Exclude the enabled plugin tags from the overlap; we merged these
      // previously to be able to resolve wildcards.
      ->diff($enabled_plugin_overlap);
    foreach ([$enabled_plugin_overlap, $disabled_plugin_overlap] as $overlap) {
      $checking_enabled = $overlap === $enabled_plugin_overlap;
      if (!$overlap->allowsNothing()) {
        $plugins_to_check_against = $checking_enabled ? $enabled_plugins : $disabled_plugins;
        $tags_plugin_report = $this->pluginsSupplyingTagsMessage($overlap, $plugins_to_check_against, $enabled_plugin_tags);
        $message = $checking_enabled ? $constraint->enabledPluginsMessage : $constraint->availablePluginsMessage;

        // Determine which element type is relevant for the violation message.
        assert(count($overlap->getAllowedElements(FALSE)) === 1);
        $overlap_tag = array_keys($overlap->getAllowedElements(FALSE))[0];
        $element_type = self::tagHasAttributeRestrictions($overlap, $overlap_tag) && array_key_exists($overlap_tag, $enabled_plugin_tags->getAllowedElements())
          ? $this->t('attribute')
          : $this->t('tag');

        // If the entirety (so not just the tag but also the attributes, and not
        // just some of the attribute values, but all of them) of the HTML
        // elements being configured to be edited via the Source Editing plugin
        // is supported by a CKEditor 5 plugin, complain. But if some attribute
        // or some attribute value is still not yet supported, do not generate a
        // violation message.
        // If there is overlap, but some attribute/attribute value is still not
        // supported, exit this iteration without generating a violation
        // message. Essentially: when assessing a particular value
        // (for example `<foo bar baz>`), only CKEditor 5 plugins providing an
        // exact match (`<foo bar baz>`) or a superset (`<foo bar baz qux>`) can
        // trigger a violation, not subsets (`<foo>`).
        if (!$source_enabled_tags->diff($overlap)->allowsNothing()) {
          continue;
        }

        // If we reach this, it means the entirety (so not just the tag but also
        // the attributes, and not just some of the attribute values, but all of
        // them) of the HTML elements being configured to be edited via the
        // Source Editing plugin's 'allowed_tags' configuration is supported by
        // a CKEditor 5 plugin. This earns a violation.
        $this->context->buildViolation($message)
          ->setParameter('@element_type', $element_type)
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
    foreach ($plugin_definitions as $plugin_id => $definition) {
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
