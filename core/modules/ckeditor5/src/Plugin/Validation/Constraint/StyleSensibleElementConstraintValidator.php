<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

// cspell:ignore enableable

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Style;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Styles can only be specified for HTML5 tags and extra classes.
 *
 * @internal
 */
class StyleSensibleElementConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PrecedingConstraintAwareValidatorTrait;
  use PluginManagerDependentValidatorTrait;
  use TextEditorObjectDependentValidatorTrait;

  /**
   * Tags whose plugins are known to not yet integrate with the Style plugin.
   *
   * To prevent the user from configuring the Style plugin and reasonably
   * expecting it to work correctly for tags of plugins that are known to
   * yet integrate with the Style plugin, generate a validation error for these.
   */
  protected const KNOWN_UNSUPPORTED_TAGS = [
    // @see https://www.drupal.org/project/drupal/issues/3117172
    '<drupal-media>',
    // @see https://github.com/ckeditor/ckeditor5/issues/13778
    '<img>',
    // @see https://github.com/ckeditor/ckeditor5/blob/39ad30090ead9dd2d54c3ac53d7f446ade9fd8ce/packages/ckeditor5-html-support/src/schemadefinitions.ts#L12-L50
    '<keygen>',
    '<applet>',
    '<basefont>',
    '<isindex>',
    '<hr>',
    '<br>',
    '<area>',
    '<command>',
    '<map>',
    '<wbr>',
    '<colgroup>',
    '<col>',
    '<datalist>',
    '<track>',
    '<source>',
    '<option>',
    '<param>',
    '<optgroup>',
    '<link>',
    '<noscript>',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($element, Constraint $constraint): void {
    if (!$constraint instanceof StyleSensibleElementConstraint) {
      throw new UnexpectedTypeException($constraint, StyleSensibleElementConstraint::class);
    }
    // The preceding constraints (in this case: CKEditor5Element) must be valid.
    if ($this->hasViolationsForPrecedingConstraints($constraint)) {
      return;
    }

    $text_editor = $this->createTextEditorObjectFromContext();

    // The single tag for which a style is specified, which we are checking now.
    $style_element = HTMLRestrictions::fromString($element);
    assert(count($style_element->getAllowedElements()) === 1);
    [$tag, $classes] = Style::getTagAndClasses($style_element);

    // Ensure the tag is in the range supported by the Style plugin.
    $superset = HTMLRestrictions::fromString('<$any-html5-element class>');
    $supported_range = $superset->merge($style_element->extractPlainTagsSubset());
    if (!$style_element->diff($supported_range)->allowsNothing()) {
      $this->context->buildViolation($constraint->nonHtml5TagMessage)
        ->setParameter('@tag', sprintf("<%s>", $tag))
        ->addViolation();
      return;
    }

    // Get the list of tags enabled by every plugin other than Style.
    $other_enabled_plugins = $this->getOtherEnabledPlugins($text_editor, 'ckeditor5_style');
    $enableable_disabled_plugins = $this->getEnableableDisabledPlugins($text_editor);

    $other_enabled_plugin_elements = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($other_enabled_plugins), $text_editor, FALSE));
    $disabled_plugin_elements = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($enableable_disabled_plugins), $text_editor, FALSE));

    // Next, validate that the classes specified for this style are not
    // supported by an enabled plugin.
    if (self::intersectionWithClasses($style_element, $other_enabled_plugin_elements)) {
      $this->context->buildViolation($constraint->conflictingEnabledPluginMessage)
        ->setParameter('@tag', sprintf("<%s>", $tag))
        ->setParameter('@classes', implode(", ", $classes))
        ->setParameter('%plugin', $this->findStyleConflictingPluginLabel($style_element))
        ->addViolation();
    }
    // Next, validate that the classes specified for this style are not
    // supported by a disabled plugin.
    elseif (self::intersectionWithClasses($style_element, $disabled_plugin_elements)) {
      $this->context->buildViolation($constraint->conflictingDisabledPluginMessage)
        ->setParameter('@tag', sprintf("<%s>", $tag))
        ->setParameter('@classes', implode(", ", $classes))
        ->setParameter('%plugin', $this->findStyleConflictingPluginLabel($style_element))
        ->addViolation();
    }

    // Finally, while the configuration is technically valid if this point was
    // reached, there are some known compatibility issues. Inform the user that
    // for that reason, this configuration must be considered invalid.
    $unsupported = $style_element->intersect(HTMLRestrictions::fromString(implode(' ', static::KNOWN_UNSUPPORTED_TAGS)));
    if (!$unsupported->allowsNothing()) {
      $this->context->buildViolation($constraint->unsupportedTagMessage)
        ->setParameter('@tag', sprintf("<%s>", $tag))
        ->addViolation();
    }
  }

  /**
   * Checks if there is an intersection on allowed 'class' attribute values.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $a
   *   One set of HTML restrictions.
   * @param \Drupal\ckeditor5\HTMLRestrictions $b
   *   Another set of HTML restrictions.
   *
   * @return bool
   *   Whether there is an intersection.
   */
  private static function intersectionWithClasses(HTMLRestrictions $a, HTMLRestrictions $b): bool {
    // Compute the intersection, but first resolve wildcards, by merging
    // tags of the other operand. Because only tags are merged, this cannot
    // introduce a 'class' attribute intersection.
    // For example: a plugin may support `<$text-container class="foo">`. On its
    // own that would not trigger an intersection, but when resolved into
    // concrete tags it could.
    $tags_from_a = array_diff(array_keys($a->getConcreteSubset()->getAllowedElements()), ['*']);
    $tags_from_b = array_diff(array_keys($b->getConcreteSubset()->getAllowedElements()), ['*']);
    $a = $a->merge(new HTMLRestrictions(array_fill_keys($tags_from_b, FALSE)));
    $b = $b->merge(new HTMLRestrictions(array_fill_keys($tags_from_a, FALSE)));
    // When a plugin allows all classes on a tag, we assume there is no
    // problem with having the style plugin adding classes to that element.
    // When allowing all classes we don't expect a specific user experience
    // so adding a class through a plugin or the style plugin is the same.
    $b_without_class_wildcard = $b->getAllowedElements();
    foreach ($b_without_class_wildcard as $allowedElement => $config) {
      // When all classes are allowed, remove the configuration so that
      // the intersect below does not include classes.
      if (!empty($config['class']) && $config['class'] === TRUE) {
        unset($b_without_class_wildcard[$allowedElement]['class']);
      }
      // HTMLRestrictions does not accept a tag with an empty array, make sure
      // to remove them here.
      if (empty($b_without_class_wildcard[$allowedElement])) {
        unset($b_without_class_wildcard[$allowedElement]);
      }
    }
    $intersection = $a->intersect(new HTMLRestrictions($b_without_class_wildcard));

    // Leverage the "GHS configuration" representation to easily find whether
    // there is an intersection for classes. Other implementations are possible.
    $intersection_as_ghs_config = $intersection->toGeneralHtmlSupportConfig();
    $ghs_config_classes = array_column($intersection_as_ghs_config, 'classes');
    return !empty($ghs_config_classes);
  }

  /**
   * Finds the plugin with elements that conflict with the style element.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $needle
   *   A style definition element: a single tag, plus the 'class' attribute,
   *   plus >=1 allowed 'class' attribute values.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label of the plugin that is conflicting with this style.
   *
   * @throws \OutOfBoundsException
   *   When a $needle is provided which does not exist among the other plugins.
   */
  private function findStyleConflictingPluginLabel(HTMLRestrictions $needle): TranslatableMarkup {
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      // We're looking to find the other plugin, not this one.
      if ($id === 'ckeditor5_style') {
        continue;
      }

      assert($definition instanceof CKEditor5PluginDefinition);
      if (!$definition->hasElements()) {
        continue;
      }

      $haystack = HTMLRestrictions::fromString(implode($definition->getElements()));
      if ($id === 'ckeditor5_sourceEditing') {
        // The Source Editing plugin's allowed elements are based on stored
        // config. This differs from all other plugins, which establish allowed
        // elements as part of their definition. Because of this, the $haystack
        // is calculated differently for Source Editing.
        $text_editor = $this->createTextEditorObjectFromContext();
        $editor_plugins = $text_editor->getSettings()['plugins'];
        if (!empty($editor_plugins['ckeditor5_sourceEditing'])) {
          $source_tags = $editor_plugins['ckeditor5_sourceEditing']['allowed_tags'];
          $haystack = HTMLRestrictions::fromString(implode($source_tags));
        }
      }
      if (self::intersectionWithClasses($needle, $haystack)) {
        return $definition->label();
      }
    }

    throw new \OutOfBoundsException();
  }

}
