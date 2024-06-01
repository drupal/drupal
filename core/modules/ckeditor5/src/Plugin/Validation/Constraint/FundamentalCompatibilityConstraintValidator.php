<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\Filter\FilterAutoP;
use Drupal\filter\Plugin\Filter\FilterUrl;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates fundamental compatibility of CKEditor 5 with the given text format.
 *
 * Fundamental requirements:
 * 1. No TYPE_MARKUP_LANGUAGE filters allowed.
 * 2. Fundamental CKEditor 5 plugins' HTML tags are allowed.
 * 3. All tags are actually creatable.
 * 4. The HTML restrictions of all TYPE_HTML_RESTRICTOR filters allow the
 *    configured CKEditor 5 plugins to work.
 *
 * @see \Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR
 *
 * @internal
 */
class FundamentalCompatibilityConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;
  use TextEditorObjectDependentValidatorTrait;

  /**
   * The fundamental CKEditor 5 plugins without which it cannot function.
   *
   * @var string[]
   */
  const FUNDAMENTAL_CKEDITOR5_PLUGINS = [
    'ckeditor5_essentials',
    'ckeditor5_paragraph',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($toolbar_item, Constraint $constraint): void {
    if (!$constraint instanceof FundamentalCompatibilityConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\FundamentalCompatibility');
    }

    $text_editor = $this->createTextEditorObjectFromContext();

    // First: the two fundamental checks against the text format. If any of
    // them adds a constraint violation, return early, because it is a
    // fundamental compatibility problem.
    $this->checkNoMarkupFilters($text_editor->getFilterFormat(), $constraint);
    if ($this->context->getViolations()->count() > 0) {
      return;
    }
    $this->checkHtmlRestrictionsAreCompatible($text_editor->getFilterFormat(), $constraint);
    if ($this->context->getViolations()->count() > 0) {
      return;
    }

    // Second: ensure that all tags can actually be created.
    $this->checkAllHtmlTagsAreCreatable($text_editor, $constraint);

    // Finally: ensure the CKEditor 5 configuration's ability to generate HTML
    // markup precisely matches that of the text format.
    $this->checkHtmlRestrictionsMatch($text_editor, $constraint);
  }

  /**
   * Checks no TYPE_MARKUP_LANGUAGE filters are present.
   *
   * Two TYPE_MARKUP_LANGUAGE filters are exempted:
   * - filter_autop: pointless but harmless to have enabled
   * - filter_url: not recommended but also harmless to have enabled
   *
   * These two commonly enabled filters with a long history in Drupal are
   * considered to be acceptable to have enabled.
   *
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The text format to validate.
   * @param \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraint $constraint
   *   The constraint to validate.
   */
  private function checkNoMarkupFilters(FilterFormatInterface $text_format, FundamentalCompatibilityConstraint $constraint): void {
    $markup_filters = static::getFiltersInFormatOfType(
      $text_format,
      FilterInterface::TYPE_MARKUP_LANGUAGE
    );
    foreach ($markup_filters as $markup_filter) {
      if ($markup_filter instanceof FilterAutoP || $markup_filter instanceof FilterUrl) {
        continue;
      }
      $this->context->buildViolation($constraint->noMarkupFiltersMessage)
        ->setParameter('%filter_label', (string) $markup_filter->getLabel())
        ->setParameter('%filter_plugin_id', $markup_filter->getPluginId())
        ->addViolation();
    }
  }

  /**
   * Checks that fundamental CKEditor 5 plugins' HTML tags are allowed.
   *
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The text format to validate.
   * @param \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraint $constraint
   *   The constraint to validate.
   */
  private function checkHtmlRestrictionsAreCompatible(FilterFormatInterface $text_format, FundamentalCompatibilityConstraint $constraint): void {
    $html_restrictions = HTMLRestrictions::fromTextFormat($text_format);
    if ($html_restrictions->isUnrestricted()) {
      return;
    }

    $fundamental = new HTMLRestrictions($this->pluginManager->getProvidedElements(self::FUNDAMENTAL_CKEDITOR5_PLUGINS));
    if (!$fundamental->diff($html_restrictions)->allowsNothing()) {
      $offending_filter = static::findHtmlRestrictorFilterNotAllowingTags($text_format, $fundamental);
      $this->context->buildViolation($constraint->nonAllowedElementsMessage)
        ->setParameter('%filter_label', (string) $offending_filter->getLabel())
        ->setParameter('%filter_plugin_id', $offending_filter->getPluginId())
        ->addViolation();
    }
  }

  /**
   * Checks the HTML restrictions match the enabled CKEditor 5 plugins' output.
   *
   * @param \Drupal\editor\EditorInterface $text_editor
   *   The text editor to validate.
   * @param \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraint $constraint
   *   The constraint to validate.
   */
  private function checkHtmlRestrictionsMatch(EditorInterface $text_editor, FundamentalCompatibilityConstraint $constraint): void {
    $html_restrictor_filters = static::getFiltersInFormatOfType(
      $text_editor->getFilterFormat(),
      FilterInterface::TYPE_HTML_RESTRICTOR
    );

    $enabled_plugins = array_keys($this->pluginManager->getEnabledDefinitions($text_editor));
    $provided_elements = $this->pluginManager->getProvidedElements($enabled_plugins, $text_editor);
    $provided = new HTMLRestrictions($provided_elements);

    foreach ($html_restrictor_filters as $filter_plugin_id => $filter) {
      $allowed = HTMLRestrictions::fromFilterPluginInstance($filter);
      $diff_allowed = $allowed->diff($provided);
      $diff_elements = $provided->diff($allowed);

      if (!$diff_allowed->allowsNothing()) {
        $this->context->buildViolation($constraint->notSupportedElementsMessage)
          ->setParameter('@list', implode(' ', $provided->toCKEditor5ElementsArray()))
          ->setParameter('@diff', implode(' ', $diff_allowed->toCKEditor5ElementsArray()))
          ->atPath("filters.$filter_plugin_id")
          ->addViolation();
      }

      if (!$diff_elements->allowsNothing()) {
        $this->context->buildViolation($constraint->missingElementsMessage)
          ->setParameter('@list', implode(' ', $provided->toCKEditor5ElementsArray()))
          ->setParameter('@diff', implode(' ', $diff_elements->toCKEditor5ElementsArray()))
          ->atPath("filters.$filter_plugin_id")
          ->addViolation();
      }
    }
  }

  /**
   * Checks all HTML tags supported by enabled CKEditor 5 plugins are creatable.
   *
   * @param \Drupal\editor\EditorInterface $text_editor
   *   The text editor to validate.
   * @param \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraint $constraint
   *   The constraint to validate.
   */
  private function checkAllHtmlTagsAreCreatable(EditorInterface $text_editor, FundamentalCompatibilityConstraint $constraint): void {
    $enabled_definitions = $this->pluginManager->getEnabledDefinitions($text_editor);
    $enabled_plugins = array_keys($enabled_definitions);

    // When arbitrary HTML is supported, all tags are creatable.
    if (in_array('ckeditor5_arbitraryHtmlSupport', $enabled_plugins, TRUE)) {
      return;
    }

    $tags_and_attributes = new HTMLRestrictions($this->pluginManager->getProvidedElements($enabled_plugins, $text_editor));
    $creatable_tags = new HTMLRestrictions($this->pluginManager->getProvidedElements($enabled_plugins, $text_editor, FALSE, TRUE));

    $needed_tags = $tags_and_attributes->extractPlainTagsSubset();
    $non_creatable_tags = $needed_tags->diff($creatable_tags);
    if (!$non_creatable_tags->allowsNothing()) {
      foreach ($non_creatable_tags->toCKEditor5ElementsArray() as $non_creatable_tag) {
        // Find the plugin which has a non-creatable tag.
        $needle = HTMLRestrictions::fromString($non_creatable_tag);
        $matching_plugins = array_filter($enabled_definitions, function (CKEditor5PluginDefinition $d) use ($needle, $text_editor) {
          if (!$d->hasElements()) {
            return FALSE;
          }
          $haystack = new HTMLRestrictions($this->pluginManager->getProvidedElements([$d->id()], $text_editor, FALSE, FALSE));
          return !$haystack->extractPlainTagsSubset()->intersect($needle)->allowsNothing();
        });
        assert(count($matching_plugins) === 1);
        $plugin_definition = reset($matching_plugins);
        assert($plugin_definition instanceof CKEditor5PluginDefinition);

        // Compute which attributes it would be able to create on this tag.
        $provided_elements = new HTMLRestrictions($this->pluginManager->getProvidedElements([$plugin_definition->id()], $text_editor, FALSE, FALSE));
        $attributes_on_tag = $provided_elements->intersect(
          new HTMLRestrictions(array_fill_keys(array_keys($needle->getAllowedElements()), TRUE))
        );

        $violation = $this->context->buildViolation($constraint->nonCreatableTagMessage)
          ->setParameter('@non_creatable_tag', $non_creatable_tag)
          ->setParameter('%plugin', $plugin_definition->label())
          ->setParameter('@attributes_on_tag', implode(', ', $attributes_on_tag->toCKEditor5ElementsArray()));

        // If this plugin has a configurable subset, associate the violation
        // with the property path pointing to this plugin's settings form.
        if (is_a($plugin_definition->getClass(), CKEditor5PluginElementsSubsetInterface::class, TRUE)) {
          $violation->atPath(sprintf('settings.plugins.%s', $plugin_definition->id()));
        }
        // If this plugin is associated with a toolbar item, associate the
        // violation with the property path pointing to the active toolbar item.
        elseif ($plugin_definition->hasToolbarItems()) {
          $toolbar_items = $plugin_definition->getToolbarItems();
          $active_toolbar_items = array_intersect(
            $text_editor->getSettings()['toolbar']['items'],
            array_keys($toolbar_items)
          );
          $violation->atPath(sprintf('settings.toolbar.items.%d', array_keys($active_toolbar_items)[0]));
        }

        $violation->addViolation();
      }
    }
  }

  /**
   * Gets the filters of the given type in this text format.
   *
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   A text format whose filters to get.
   * @param int $filter_type
   *   One of FilterInterface::TYPE_*.
   * @param callable|null $extra_requirements
   *   An optional callable that can check a filter of this type for additional
   *   conditions to be met. Must return TRUE when it meets the conditions,
   *   FALSE otherwise.
   *
   * @return iterable|\Drupal\filter\Plugin\FilterInterface[]
   *   An iterable of matched filter plugins.
   */
  private static function getFiltersInFormatOfType(FilterFormatInterface $text_format, int $filter_type, ?callable $extra_requirements = NULL): iterable {
    assert(in_array($filter_type, [
      FilterInterface::TYPE_MARKUP_LANGUAGE,
      FilterInterface::TYPE_HTML_RESTRICTOR,
      FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
      FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
    ]));
    foreach ($text_format->filters() as $id => $filter) {
      if ($filter->status && $filter->getType() === $filter_type && ($extra_requirements === NULL || $extra_requirements($filter))) {
        yield $id => $filter;
      }
    }
  }

  /**
   * Analyzes a text format to find the filter not allowing required tags.
   *
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   A text format whose filters to check for compatibility.
   * @param \Drupal\ckeditor5\HTMLRestrictions $required
   *   A set of HTML restrictions, listing required HTML tags.
   *
   * @return \Drupal\filter\Plugin\FilterInterface
   *   The filter plugin instance not allowing the required tags.
   *
   * @throws \InvalidArgumentException
   */
  private static function findHtmlRestrictorFilterNotAllowingTags(FilterFormatInterface $text_format, HTMLRestrictions $required): FilterInterface {
    // Get HTML restrictor filters that actually restrict HTML.
    $filters = static::getFiltersInFormatOfType(
      $text_format,
      FilterInterface::TYPE_HTML_RESTRICTOR,
      function (FilterInterface $filter) {
        return $filter->getHTMLRestrictions() !== FALSE;
      }
    );

    foreach ($filters as $filter) {
      // Return any filter not allowing >=1 of the required tags.
      if (!$required->diff(HTMLRestrictions::fromFilterPluginInstance($filter))->allowsNothing()) {
        return $filter;
      }
    }

    throw new \InvalidArgumentException('This text format does not have a "tags allowed" restriction that excludes the required tags.');
  }

}
