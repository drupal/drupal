<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterFormatInterface;
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
 * 3. The HTML restrictions of all TYPE_HTML_RESTRICTOR filters allow the
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
  public function validate($toolbar_item, Constraint $constraint) {
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

    // Finally: ensure the CKEditor 5 configuration's ability to generate HTML
    // markup precisely matches that of the text format.
    $this->checkHtmlRestrictionsMatch($text_editor, $constraint);
  }

  /**
   * Checks no TYPE_MARKUP_LANGUAGE filters are present.
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
      $this->context->buildViolation($constraint->noMarkupFiltersMessage)
        ->setParameter('%filter_label', $markup_filter->getLabel())
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
    $fundamental = new HTMLRestrictions($this->pluginManager->getProvidedElements(self::FUNDAMENTAL_CKEDITOR5_PLUGINS));

    // @todo Remove in favor of HTMLRestrictions::diff() in https://www.drupal.org/project/drupal/issues/3231334
    $html_restrictions = $text_format->getHtmlRestrictions();
    $minimum_tags = array_keys($fundamental->getAllowedElements());
    $forbidden_minimum_tags = isset($html_restrictions['forbidden_tags'])
      ? array_diff($minimum_tags, $html_restrictions['forbidden_tags'])
      : [];
    if (!empty($forbidden_minimum_tags)) {
      $offending_filter = static::findHtmlRestrictorFilterForbiddingTags($text_format, $minimum_tags);
      $this->context->buildViolation($constraint->forbiddenElementsMessage)
        ->setParameter('%filter_label', $offending_filter->getLabel())
        ->setParameter('%filter_plugin_id', $offending_filter->getPluginId())
        ->addViolation();
    }

    // @todo Remove early return in https://www.drupal.org/project/drupal/issues/3231334
    if (!isset($html_restrictions['allowed'])) {
      return;
    }
    if (!$fundamental->diff(HTMLRestrictions::fromTextFormat($text_format))->isEmpty()) {
      $offending_filter = static::findHtmlRestrictorFilterNotAllowingTags($text_format, $fundamental);
      $this->context->buildViolation($constraint->nonAllowedElementsMessage)
        ->setParameter('%filter_label', $offending_filter->getLabel())
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
    $provided = $this->pluginManager->getProvidedElements($enabled_plugins, $text_editor);

    foreach ($html_restrictor_filters as $filter_plugin_id => $filter) {
      $allowed = HTMLRestrictions::fromFilterPluginInstance($filter);
      $provided = new HTMLRestrictions($provided);
      $diff_allowed = $allowed->diff($provided);
      $diff_elements = $provided->diff($allowed);

      if (!$diff_allowed->isEmpty()) {
        $this->context->buildViolation($constraint->notSupportedElementsMessage)
          ->setParameter('@list', $provided->toFilterHtmlAllowedTagsString())
          ->setParameter('@diff', $diff_allowed->toFilterHtmlAllowedTagsString())
          ->atPath("filters.$filter_plugin_id")
          ->addViolation();
      }

      if (!$diff_elements->isEmpty()) {
        $this->context->buildViolation($constraint->missingElementsMessage)
          ->setParameter('@list', $provided->toFilterHtmlAllowedTagsString())
          ->setParameter('@diff', $diff_elements->toFilterHtmlAllowedTagsString())
          ->atPath("filters.$filter_plugin_id")
          ->addViolation();
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
  private static function getFiltersInFormatOfType(FilterFormatInterface $text_format, int $filter_type, callable $extra_requirements = NULL): iterable {
    assert(in_array($filter_type, [
      FilterInterface::TYPE_MARKUP_LANGUAGE,
      FilterInterface::TYPE_HTML_RESTRICTOR,
      FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
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
   * @param string[] $required_tags
   *   A list of HTML tags that are required.
   *
   * @return \Drupal\filter\Plugin\FilterInterface
   *   The filter plugin instance not allowing the required tags.
   *
   * @throws \InvalidArgumentException
   */
  private static function findHtmlRestrictorFilterForbiddingTags(FilterFormatInterface $text_format, array $required_tags): FilterInterface {
    // Get HTML restrictor filters that actually restrict HTML.
    $filters = static::getFiltersInFormatOfType(
      $text_format,
      FilterInterface::TYPE_HTML_RESTRICTOR,
      function (FilterInterface $filter) {
        return $filter->getHTMLRestrictions() !== FALSE;
      }
    );

    foreach ($filters as $filter) {
      $restrictions = $filter->getHTMLRestrictions();

      // @todo Fix
      //   \Drupal\filter_test\Plugin\Filter\FilterTestRestrictTagsAndAttributes::getHTMLRestrictions(),
      //   whose computed value for forbidden_tags does not comply with the API
      //   https://www.drupal.org/project/drupal/issues/3231331.
      if (array_keys($restrictions['forbidden_tags']) != range(0, count($restrictions['forbidden_tags']))) {
        $restrictions['forbidden_tags'] = array_keys($restrictions['forbidden_tags']);
      }
      if (isset($restrictions['forbidden_tags']) && !empty(array_intersect($required_tags, $restrictions['forbidden_tags']))) {
        return $filter;
      }
    }

    throw new \InvalidArgumentException('This text format does not have a "tags forbidden" restriction that includes the required tags.');
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
      if (!$required->diff(HTMLRestrictions::fromFilterPluginInstance($filter))->isEmpty()) {
        return $filter;
      }
    }

    throw new \InvalidArgumentException('This text format does not have a "tags allowed" restriction that excludes the required tags.');
  }

}
