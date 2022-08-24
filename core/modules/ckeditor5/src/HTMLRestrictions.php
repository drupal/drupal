<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\DiffArray;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\Filter\FilterHtml;
use Drupal\filter\Plugin\FilterInterface;
use Masterminds\HTML5\Elements;

/**
 * Represents a set of HTML restrictions.
 *
 * This is a value object to represent HTML restrictions as defined by
 * \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions(). It:
 * - accepts the array structure documented on that interface as its constructor
 *   argument
 * - provides convenience constructors for common sources of HTML restrictions
 * - can transform this into multiple representations: a single string
 *   representation historically used by Drupal, a list
 *   representation used by CKEditor 5 and a complex array structure used by
 *   CKEditor 5's General HTML Support plugin
 * - offers difference, intersection and union operations.
 *
 * This makes it significantly simpler to reason about different sets of HTML
 * restrictions and perform complex comparisons by performing these simple
 * operations.
 *
 * @see FilterInterface::getHTMLRestrictions()
 *
 * NOTE: Wildcard tags are not a concept of the Drupal filter system or HTML
 * filter; they are a CKEditor 5 concept. This allows CKEditor 5 plugins to
 * convey a whole range of elements which they support setting certain
 * attributes or attribute values on. For example: alignment.
 *
 * @see ::WILDCARD_ELEMENT_METHODS
 *
 * NOTE: Currently only supports the 'allowed' portion.
 * @todo Add support for "forbidden" tags in https://www.drupal.org/project/drupal/issues/3231336
 *
 * @internal
 */
final class HTMLRestrictions {

  /**
   * An array of allowed elements.
   *
   * @var array
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  private $elements;

  /**
   * Whether unrestricted, in other words: arbitrary HTML allowed.
   *
   * Used for when FilterFormatInterface::getHTMLRestrictions() returns `FALSE`,
   * e.g. in case of the default "Full HTML" text format.
   *
   * @var bool
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  private $unrestricted = FALSE;

  /**
   * Wildcard types, and the methods that return tags the wildcard represents.
   *
   * @var string[]
   */
  private const WILDCARD_ELEMENT_METHODS = [
    '$any-html5-element' => 'getHtml5ElementList',
    '$text-container' => 'getTextContainerElementList',
  ];

  /**
   * Constructs a set of HTML restrictions.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  public function __construct(array $elements) {
    self::validateAllowedRestrictionsPhase1($elements);
    self::validateAllowedRestrictionsPhase2($elements);
    self::validateAllowedRestrictionsPhase3($elements);
    self::validateAllowedRestrictionsPhase4($elements);
    $this->elements = $elements;
  }

  /**
   * Validates allowed elements — phase 1: shape of keys.
   *
   * Confirms each of the top-level array keys:
   * - Is a string
   * - Does not contain leading or trailing whitespace
   * - Is a tag name, not a tag, e.g. `div` not `<div>`
   * - Is a valid HTML tag name (or the global attribute `*` tag).
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase1(array $elements): void {
    if (!is_array($elements) || !Inspector::assertAllStrings(array_keys($elements))) {
      throw new \InvalidArgumentException('An array of key-value pairs must be provided, with HTML tag names as keys.');
    }
    foreach (array_keys($elements) as $html_tag_name) {
      if (trim($html_tag_name) !== $html_tag_name) {
        throw new \InvalidArgumentException(sprintf('The "%s" HTML tag contains trailing or leading whitespace.', $html_tag_name));
      }
      if ($html_tag_name[0] === '<' || $html_tag_name[-1] === '>') {
        throw new \InvalidArgumentException(sprintf('"%s" is not a HTML tag name, it is an actual HTML tag. Omit the angular brackets.', $html_tag_name));
      }
      if (self::isWildcardTag($html_tag_name)) {
        continue;
      }
      // Special case: the global attribute `*` HTML tag.
      // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
      // @see validateAllowedRestrictionsPhase2()
      // @see validateAllowedRestrictionsPhase4()
      if ($html_tag_name === '*') {
        continue;
      }
      // HTML elements must have a valid tag name.
      // @see https://html.spec.whatwg.org/multipage/syntax.html#syntax-tag-name
      // @see https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
      if (!preg_match('/^[a-z][0-9a-z\-]*$/', strtolower($html_tag_name))) {
        throw new \InvalidArgumentException(sprintf('"%s" is not a valid HTML tag name.', $html_tag_name));
      }
    }
  }

  /**
   * Validates allowed elements — phase 2: shape of values.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase2(array $elements): void {
    foreach ($elements as $html_tag_name => $html_tag_restrictions) {
      // The global attribute `*` HTML tag is a special case: it allows
      // specifying specific attributes that are allowed on all tags (f.e.
      // `lang`) or disallowed on all tags (f.e. `style`) as translations and
      // security are concerns orthogonal to the configured HTML restrictions
      // of a text format.
      // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
      // @see validateAllowedRestrictionsPhase4()
      if ($html_tag_name === '*' && !is_array($html_tag_restrictions)) {
        throw new \InvalidArgumentException(sprintf('The value for the special "*" global attribute HTML tag must be an array of attribute restrictions.'));
      }

      // The value must be either a boolean (FALSE means no attributes are
      // allowed, TRUE means all attributes are allowed), or an array of allowed
      // The value must be either:
      // - An array of allowed attribute names OR
      // - A boolean (where FALSE means no attributes are allowed, and TRUE
      //   means all attributes are allowed).
      if (is_bool($html_tag_restrictions)) {
        continue;
      }
      if (!is_array($html_tag_restrictions)) {
        throw new \InvalidArgumentException(sprintf('The value for the "%s" HTML tag is neither a boolean nor an array of attribute restrictions.', $html_tag_name));
      }
      if ($html_tag_restrictions === []) {
        throw new \InvalidArgumentException(sprintf('The value for the "%s" HTML tag is an empty array. This is not permitted, specify FALSE instead to indicate no attributes are allowed. Otherwise, list allowed attributes.', $html_tag_name));
      }
    }
  }

  /**
   * Validates allowed elements — phase 3: HTML tag attribute restriction keys.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase3(array $elements): void {
    foreach ($elements as $html_tag_name => $html_tag_restrictions) {
      if (!is_array($html_tag_restrictions)) {
        continue;
      }
      if (!Inspector::assertAllStrings(array_keys($html_tag_restrictions))) {
        throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has attribute restrictions, but it is not an array of key-value pairs, with HTML tag attribute names as keys.', $html_tag_name));
      }

      foreach ($html_tag_restrictions as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
        if (trim($html_tag_attribute_name) !== $html_tag_attribute_name) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "%s" which contains whitespace. Omit the whitespace.', $html_tag_name, $html_tag_attribute_name));
        }
        if ($html_tag_attribute_name === '*') {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "*". This implies all attributes are allowed. Remove the attribute restriction instead, or use a prefix (`*-foo`), infix (`*-foo-*`) or suffix (`foo-*`) wildcard restriction instead.', $html_tag_name));
        }
      }
    }
  }

  /**
   * Validates allowed elements — phase 4: HTML tag attr restriction values.
   *
   * @param array $elements
   *   The allowed elements.
   *
   * @throws \InvalidArgumentException
   */
  private static function validateAllowedRestrictionsPhase4(array $elements): void {
    foreach ($elements as $html_tag_name => $html_tag_restrictions) {
      if (!is_array($html_tag_restrictions)) {
        continue;
      }

      foreach ($html_tag_restrictions as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
        // The value must be either TRUE (meaning all values for this
        // are allowed), or an array of allowed attribute values.
        if ($html_tag_attribute_restrictions === TRUE) {
          continue;
        }
        // Special case: the global attribute `*` HTML tag.
        // The global attribute `*` HTML tag is a special case: it allows
        // specifying specific attributes that are allowed on all tags (f.e.
        // `lang`) or disallowed on all tags (f.e. `style`) as translations and
        // security are concerns orthogonal to the configured HTML restrictions
        // of a text format.
        // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
        // @see validateAllowedRestrictionsPhase2()
        if ($html_tag_name === '*' && $html_tag_attribute_restrictions === FALSE) {
          continue;
        }
        if (!is_array($html_tag_attribute_restrictions)) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "%s" which is neither TRUE nor an array of attribute value restrictions.', $html_tag_name, $html_tag_attribute_name));
        }
        if ($html_tag_attribute_restrictions === []) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has an attribute restriction "%s" which is set to the empty array. This is not permitted, specify either TRUE to allow all attribute values, or list the attribute value restrictions.', $html_tag_name, $html_tag_attribute_name));
        }
        // @codingStandardsIgnoreLine
        if (!Inspector::assertAll(function ($v) { return $v === TRUE; }, $html_tag_attribute_restrictions)) {
          throw new \InvalidArgumentException(sprintf('The "%s" HTML tag has attribute restriction "%s", but it is not an array of key-value pairs, with HTML tag attribute values as keys and TRUE as values.', $html_tag_name, $html_tag_attribute_name));
        }
      }
    }
  }

  /**
   * Creates the empty set of HTML restrictions: nothing is allowed.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  public static function emptySet(): HTMLRestrictions {
    return new self([]);
  }

  /**
   * Whether this set of HTML restrictions is unrestricted.
   *
   * @return bool
   */
  public function isUnrestricted(): bool {
    return $this->unrestricted;
  }

  /**
   * Whether this set of HTML restrictions allows nothing.
   *
   * @return bool
   *
   * @see ::emptySet()
   */
  public function allowsNothing(): bool {
    return count($this->elements) === 0
      // If there are only forbidden attributes on the global attribute `*` HTML
      // tag, that is equivalent to the set of restrictions being empty.
      || count($this->elements) === 1 && isset($this->elements['*']) && empty(array_filter($this->elements['*']));
  }

  /**
   * Constructs a set of HTML restrictions matching the given text format.
   *
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   A filter plugin instance to construct a HTML restrictions object for.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  public static function fromFilterPluginInstance(FilterInterface $filter): HTMLRestrictions {
    return self::fromObjectWithHtmlRestrictions($filter);
  }

  /**
   * Constructs a set of HTML restrictions matching the given text format.
   *
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   A text format to construct a HTML restrictions object for.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  public static function fromTextFormat(FilterFormatInterface $text_format): HTMLRestrictions {
    return self::fromObjectWithHtmlRestrictions($text_format);
  }

  /**
   * Constructs an unrestricted set of HTML restrictions.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   */
  private static function unrestricted(): self {
    // @todo Refine in https://www.drupal.org/project/drupal/issues/3231336, including adding support for all operations.
    $restrictions = HTMLRestrictions::emptySet();
    $restrictions->unrestricted = TRUE;
    return $restrictions;
  }

  /**
   * Constructs a set of HTML restrictions matching the given object.
   *
   * Note: there is no interface for the ::getHTMLRestrictions() method that
   * both text filter plugins and the text format configuration entity type
   * implement. To avoid duplicating this logic, this private helper method
   * exists: to simplify the two public static methods that each accept one of
   * those two interfaces.
   *
   * @param \Drupal\filter\Plugin\FilterInterface|\Drupal\filter\FilterFormatInterface $object
   *   A text format or filter plugin instance to construct a HTML restrictions
   *   object for.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *
   * @see ::fromFilterPluginInstance()
   * @see ::fromTextFormat()
   */
  private static function fromObjectWithHtmlRestrictions(object $object): HTMLRestrictions {
    if (!method_exists($object, 'getHTMLRestrictions')) {
      throw new \InvalidArgumentException();
    }

    if ($object->getHtmlRestrictions() === FALSE) {
      // @todo Refine in https://www.drupal.org/project/drupal/issues/3231336
      return self::unrestricted();
    }

    $restrictions = $object->getHTMLRestrictions();
    if (!isset($restrictions['allowed'])) {
      // @todo Handle HTML restrictor filters that only set forbidden_tags
      //   https://www.drupal.org/project/ckeditor5/issues/3231336.
      throw new \DomainException('text formats with only filters that forbid tags rather than allowing tags are not yet supported.');
    }

    // When allowing all tags on an attribute, transform FilterHtml output from
    // ['tag' => ['*'=> TRUE]] to ['tag' => TRUE]
    foreach ($restrictions['allowed'] as $element => $attributes) {
      if (is_array($attributes) && isset($attributes['*']) && $attributes['*'] === TRUE) {
        $restrictions['allowed'][$element] = TRUE;
      }
    }

    $allowed = $restrictions['allowed'];

    return new self($allowed);
  }

  /**
   * Parses a string of HTML restrictions into a HTMLRestrictions value object.
   *
   * @param string $elements_string
   *   A string representing a list of allowed HTML elements.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *
   * @see ::toFilterHtmlAllowedTagsString()
   * @see ::toCKEditor5ElementsArray()
   */
  public static function fromString(string $elements_string): HTMLRestrictions {
    // Preprocess wildcard tags: convert `<$text-container>` to
    // `<preprocessed-wildcard-text-container__>` and `<*>` to
    // `<preprocessed-global-attribute__>`.
    // Note: unknown wildcard tags will trigger a validation error in
    // ::validateAllowedRestrictionsPhase1().
    $replaced_wildcard_tags = [];
    $elements_string = preg_replace_callback('/<(\$[a-z][0-9a-z\-]*|\*)/', function ($matches) use (&$replaced_wildcard_tags) {
      $wildcard_tag_name = $matches[1];
      $replacement = $wildcard_tag_name === '*'
        ? 'preprocessed-global-attribute__'
        : sprintf("preprocessed-wildcard-%s__", substr($wildcard_tag_name, 1));
      $replaced_wildcard_tags[$replacement] = $wildcard_tag_name;
      return "<$replacement";
    }, $elements_string);

    // Reuse the parsing logic from FilterHtml::getHTMLRestrictions().
    $configuration = ['settings' => ['allowed_html' => $elements_string]];
    $filter = new FilterHtml($configuration, 'filter_html', ['provider' => 'filter']);
    $allowed_elements = $filter->getHTMLRestrictions()['allowed'];
    // Omit the broad wildcard addition that FilterHtml::getHTMLRestrictions()
    // always sets; it is specific to how FilterHTML works and irrelevant here.
    unset($allowed_elements['*']);
    // @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
    // @todo remove this in https://www.drupal.org/project/drupal/issues/3226368
    unset($allowed_elements['__zqh6vxfbk3cg__']);

    // Postprocess tag wildcards: convert
    // `<preprocessed-wildcard-text-container__>` to `<$text-container>`.
    foreach ($replaced_wildcard_tags as $processed => $original) {
      if (isset($allowed_elements[$processed])) {
        $allowed_elements[$original] = $allowed_elements[$processed];
        unset($allowed_elements[$processed]);
      }
    }

    // When allowing all tags on an attribute, transform FilterHtml output from
    // ['tag' => ['*'=> TRUE]] to ['tag' => TRUE]
    foreach ($allowed_elements as $element => $attributes) {
      if (is_array($attributes) && isset($attributes['*']) && $attributes['*'] === TRUE) {
        $allowed_elements[$element] = TRUE;
      }
    }

    return new self($allowed_elements);
  }

  /**
   * Computes difference of two HTML restrictions, with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are not allowed in $other.
   */
  public function diff(HTMLRestrictions $other): HTMLRestrictions {
    return self::applyOperation($this, $other, 'doDiff');
  }

  /**
   * Computes difference of two HTML restrictions, without wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are not allowed in $other.
   */
  private function doDiff(HTMLRestrictions $other): HTMLRestrictions {
    $diff_elements = array_filter(
      DiffArray::diffAssocRecursive($this->elements, $other->elements),
      // DiffArray::diffAssocRecursive() provides a good start, but additional
      // filtering is necessary due to the specific semantics of an HTML
      // restrictions array, where:
      // - A value of FALSE for a given tag/attribute disallows all
      //   attributes/ /attribute values for that tag/attribute.
      // - An array value for a given tag/attribute provides an array keyed by
      //   specific attributes/attribute values with boolean values determining
      //   if they are allowed or not.
      // - A value of TRUE for a given tag/attribute permits all attributes/attribute
      //   values for that tag/attribute.
      // @see \Drupal\filter\Entity\FilterFormat::getHtmlRestrictions()
      function ($value, string $tag) use ($other) {
        // If this HTML restrictions object contains a tag that the other did
        // not contain at all: keep the DiffArray result.
        if (!array_key_exists($tag, $other->elements)) {
          return TRUE;
        }

        // All subsequent checks can assume that $other contains an entry for
        // this tag.

        // If this HTML restrictions object does not allow any attributes for
        // this tag, then the other is at least equally restrictive: drop the
        // DiffArray result.
        if ($value === FALSE) {
          return FALSE;
        }
        // If this HTML restrictions object allows any attributes for this
        // tag, then the other is at most equally permissive: keep the
        // DiffArray result.
        if ($value === TRUE) {
          return TRUE;
        }
        // Otherwise, this HTML restrictions object allows specific attributes
        // only. DiffArray only knows to compare arrays. When the other object
        // has a non-array value for this tag, interpret those values correctly.
        assert(is_array($value));
        // The other object is more restrictive regarding allowed attributes
        // for this tag: keep the DiffArray result.
        if ($other->elements[$tag] === FALSE) {
          return TRUE;
        }
        // The other object is more permissive regarding allowed attributes
        // for this tag: drop the DiffArray result.
        if ($other->elements[$tag] === TRUE) {
          return FALSE;
        }
        // Both objects have lists of allowed attributes: keep the DiffArray
        // result and apply postprocessing after this array_filter() call,
        // because this can only affect tag-level differences.
        // @see ::validateAllowedRestrictionsPhase3()
        assert(is_array($other->elements[$tag]));
        return TRUE;
      },
      ARRAY_FILTER_USE_BOTH
    );

    // Attribute-level postprocessing for two special cases:
    // - wildcard attribute names
    // - per attribute name: attribute value restrictions in $this vs all values
    //   allowed in $other
    foreach ($diff_elements as $tag => $tag_config) {
      // If there are no per-attribute restrictions for this tag in either
      // operand, then no postprocessing is needed.
      if (!is_array($tag_config) || !(isset($other->elements[$tag]) && is_array($other->elements[$tag]))) {
        continue;
      }

      // Special case: wildcard attributes, and the ability to define
      // restrictions for all concrete attributes matching them using:
      // - prefix wildcard, f.e. `*-foo`
      // - infix wildcard, f.e. `*-entity-*`
      // - suffix wildcard, f.e. `data-*`, to match `data-foo`, `data-bar`, etc.
      $wildcard_attributes = array_filter(array_keys($other->elements[$tag]), [__CLASS__, 'isWildcardAttributeName']);
      foreach ($wildcard_attributes as $wildcard_attribute_name) {
        $regex = self::getRegExForWildCardAttributeName($wildcard_attribute_name);
        foreach ($tag_config as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
          // If a wildcard attribute name (f.e. `data-*`) is allowed in $other
          // with the same attribute value restrictions (e.g. TRUE to allow all
          // attribute values or an array of specific allowed attribute values),
          // then all concrete matches (f.e. `data-foo`, `data-bar`, etc.) are
          // allowed and should be explicitly omitted from the difference.
          if ($html_tag_attribute_restrictions === $other->elements[$tag][$wildcard_attribute_name] && preg_match($regex, $html_tag_attribute_name) === 1) {
            unset($tag_config[$html_tag_attribute_name]);
          }
        }
      }

      // Attribute value restrictions in $this, all values allowed in $other.
      foreach ($tag_config as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
        if (is_array($html_tag_attribute_restrictions) && isset($other->elements[$tag][$html_tag_attribute_name]) && $other->elements[$tag][$html_tag_attribute_name] === TRUE) {
          unset($tag_config[$html_tag_attribute_name]);
        }
      }

      // Ensure $diff_elements continues to be structured in a way that is valid
      // for a HTMLRestrictions object to be constructed from it.
      if ($tag_config !== []) {
        $diff_elements[$tag] = $tag_config;
      }
      else {
        unset($diff_elements[$tag]);
      }
    }

    return new self($diff_elements);
  }

  /**
   * Computes intersection of two HTML restrictions, with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are also allowed in $other.
   */
  public function intersect(HTMLRestrictions $other): HTMLRestrictions {
    return self::applyOperation($this, $other, 'doIntersect');
  }

  /**
   * Computes intersection of two HTML restrictions, without wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are also allowed in $other.
   */
  public function doIntersect(HTMLRestrictions $other): HTMLRestrictions {
    $intersection_based_on_tags = array_intersect_key($this->elements, $other->elements);
    $intersection = [];
    // Additional filtering is necessary beyond the array_intersect_key that
    // computed $intersection_based_on_tags because tag configuration can have
    // boolean values that have different logic than array values.
    foreach (array_keys($intersection_based_on_tags) as $tag) {
      // If either does not allow attributes, neither does the intersection.
      if ($this->elements[$tag] === FALSE || $other->elements[$tag] === FALSE) {
        $intersection[$tag] = FALSE;
        continue;
      }
      // If both allow all attributes, so does the intersection.
      if ($this->elements[$tag] === TRUE && $other->elements[$tag] === TRUE) {
        $intersection[$tag] = TRUE;
        continue;
      }
      // If the first allows all attributes, return the second.
      if ($this->elements[$tag] === TRUE) {
        $intersection[$tag] = $other->elements[$tag];
        continue;
      }
      // And vice versa.
      if ($other->elements[$tag] === TRUE) {
        $intersection[$tag] = $this->elements[$tag];
        continue;
      }
      // In all other cases, we need to return the most restrictive
      // intersection of per-attribute restrictions.
      // @see ::validateAllowedRestrictionsPhase3()
      assert(is_array($this->elements[$tag]));
      assert(is_array($other->elements[$tag]));
      $intersection[$tag] = [];
      $attributes_intersection = array_intersect_key($this->elements[$tag], $other->elements[$tag]);
      foreach (array_keys($attributes_intersection) as $attr) {
        // If both allow all attribute values, so does the intersection.
        if ($this->elements[$tag][$attr] === TRUE && $other->elements[$tag][$attr] === TRUE) {
          $intersection[$tag][$attr] = TRUE;
          continue;
        }
        // If the first allows all attribute values, return the second.
        if ($this->elements[$tag][$attr] === TRUE) {
          $intersection[$tag][$attr] = $other->elements[$tag][$attr];
          continue;
        }
        // And vice versa.
        if ($other->elements[$tag][$attr] === TRUE) {
          $intersection[$tag][$attr] = $this->elements[$tag][$attr];
          continue;
        }
        // If either allows no attribute values, nor does the intersection.
        if ($this->elements[$tag][$attr] === FALSE || $other->elements[$tag][$attr] === FALSE) {
          // Special case: the global attribute `*` HTML tag.
          // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
          // @see validateAllowedRestrictionsPhase2()
          // @see validateAllowedRestrictionsPhase4()
          assert($tag === '*');
          $intersection[$tag][$attr] = FALSE;
          continue;
        }
        assert(is_array($this->elements[$tag][$attr]));
        assert(is_array($other->elements[$tag][$attr]));
        $intersection[$tag][$attr] = array_intersect_key($this->elements[$tag][$attr], $other->elements[$tag][$attr]);
        // It is not permitted to specify an empty attribute value
        // restrictions array.
        if (empty($intersection[$tag][$attr])) {
          unset($intersection[$tag][$attr]);
        }
      }

      // HTML tags must not have an empty array of allowed attributes.
      if ($intersection[$tag] === []) {
        $intersection[$tag] = FALSE;
        // Special case: the global attribute `*` HTML tag.
        // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
        // @see validateAllowedRestrictionsPhase2()
        // @see validateAllowedRestrictionsPhase4()
        if ($tag === '*') {
          unset($intersection[$tag]);
        }
      }
    }

    // Special case: wildcard attributes, and the ability to define restrictions
    // for all concrete attributes matching them using:
    // - prefix wildcard, f.e. `*-foo`
    // - infix wildcard, f.e. `*-entity-*`
    // - suffix wildcard, f.e. `data-*`, to match `data-foo`, `data-bar`, etc.
    foreach ($intersection as $tag => $tag_config) {
      // If there are no per-attribute restrictions for this tag in either
      // operand, then no wildcard attribute postprocessing is needed.
      if (!(is_array($this->elements[$tag]) && is_array($other->elements[$tag]))) {
        continue;
      }
      $other_wildcard_attributes = array_filter(array_keys($other->elements[$tag]), [__CLASS__, 'isWildcardAttributeName']);
      $this_wildcard_attributes = array_filter(array_keys($this->elements[$tag]), [__CLASS__, 'isWildcardAttributeName']);

      // If the same wildcard attribute restrictions are present in both or
      // neither, no adjustment necessary: the intersection is already correct.
      $in_both = array_intersect($other_wildcard_attributes, $this_wildcard_attributes);
      $other_wildcard_attributes = array_diff($other_wildcard_attributes, $in_both);
      $this_wildcard_attributes = array_diff($this_wildcard_attributes, $in_both);
      $wildcard_attributes_to_analyze = array_merge($other_wildcard_attributes, $this_wildcard_attributes);
      if (empty($wildcard_attributes_to_analyze)) {
        continue;
      }

      // Otherwise, the wildcard attribute name (f.e. `data-*`) is allowed in
      // one of the two with the same attribute value restrictions (e.g. TRUE to
      // allow all attribute values, or an array of specific allowed attribute
      // values), and the intersection must contain the most restrictive
      // configuration.
      foreach ($wildcard_attributes_to_analyze as $wildcard_attribute_name) {
        $other_has_wildcard = isset($other->elements[$tag][$wildcard_attribute_name]);
        $wildcard_operand = $other_has_wildcard ? $other : $this;
        $concrete_operand = $other_has_wildcard ? $this : $other;
        $concrete_tag_config = $concrete_operand->elements[$tag];
        $wildcard_attribute_restriction = $wildcard_operand->elements[$tag][$wildcard_attribute_name];
        $regex = self::getRegExForWildCardAttributeName($wildcard_attribute_name);
        foreach ($concrete_tag_config as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
          if ($html_tag_attribute_restrictions === $wildcard_attribute_restriction && preg_match($regex, $html_tag_attribute_name) === 1) {
            $tag_config = $tag_config === FALSE ? [] : $tag_config;
            $tag_config[$html_tag_attribute_name] = $html_tag_attribute_restrictions;
          }
        }
        $intersection[$tag] = $tag_config;
      }
    }

    return new self($intersection);
  }

  /**
   * Merge arrays of allowed elements according to HTMLRestrictions rules.
   *
   * @param array $array1
   *   The first array of allowed elements.
   * @param array $array2
   *   The second array of allowed elements.
   *
   * @return array
   *   Merged array of allowed elements.
   */
  private static function mergeAllowedElementsLevel(array $array1, array $array2): array {
    $union = [];
    $array1_keys = array_keys($array1);
    $array2_keys = array_keys($array2);
    $common_keys = array_intersect($array1_keys, $array2_keys);
    if (count($common_keys) === 0) {
      // There are no keys in common, simply append the arrays.
      $union = $array1 + $array2;
    }
    else {
      // For all the distinct keys, append them to the result.
      $filter_keys = array_flip($common_keys);
      // Add all unique keys from $array1.
      $union += array_diff_key($array1, $filter_keys);
      // Add all unique keys from $array2.
      $union += array_diff_key($array2, $filter_keys);

      // There are some keys in common that need to be merged.
      foreach ($common_keys as $key) {
        $value1 = $array1[$key];
        $value2 = $array2[$key];
        $value1_is_bool = is_bool($value1);
        $value2_is_bool = is_bool($value2);

        // When both values are boolean, combine the two.
        if ($value1_is_bool && $value2_is_bool) {
          $union[$key] = $value1 || $value2;
        }
        // When only one value is a boolean, take the most permissive result:
        // - when the value it TRUE, keep TRUE as it is the most permissive
        // - when the value is FALSE, take the other value.
        elseif ($value1_is_bool) {
          $union[$key] = $value1 ?: $value2;
        }
        elseif ($value2_is_bool) {
          $union[$key] = $value2 ?: $value1;
        }
        // Process nested arrays, in this case it correspond to tag attributes
        // configuration.
        elseif (is_array($value1) && is_array($value2)) {
          $union[$key] = self::mergeAllowedElementsLevel($value1, $value2);
        }
      }
    }
    // Make sure the order of the union array matches the order of the keys in
    // the arrays provided.
    $ordered = [];
    foreach (array_merge($array1_keys, $array2_keys) as $key) {
      $ordered[$key] = $union[$key];
    }
    return $ordered;
  }

  /**
   * Computes set union of two HTML restrictions, with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $other
   *   The HTML restrictions to compare to.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   Returns a new HTML restrictions value object with all the elements that
   *   are either allowed in $this or in $other.
   */
  public function merge(HTMLRestrictions $other): HTMLRestrictions {
    $union = self::mergeAllowedElementsLevel($this->elements, $other->elements);

    // Special case: wildcard attributes, and the ability to define restrictions
    // for all concrete attributes matching them using:
    // - prefix wildcard, f.e. `*-foo`
    // - infix wildcard, f.e. `*-entity-*`
    // - suffix wildcard, f.e. `data-*`, to match `data-foo`, `data-bar`, etc.
    foreach ($union as $tag => $tag_config) {
      // If there are no per-attribute restrictions for this tag, then no
      // wildcard attribute postprocessing is needed.
      if (!is_array($tag_config)) {
        continue;
      }
      $wildcard_attributes = array_filter(array_keys($tag_config), [__CLASS__, 'isWildcardAttributeName']);
      foreach ($wildcard_attributes as $wildcard_attribute_name) {
        $regex = self::getRegExForWildCardAttributeName($wildcard_attribute_name);
        foreach ($tag_config as $html_tag_attribute_name => $html_tag_attribute_restrictions) {
          // The wildcard attribute restriction itself must be kept.
          if ($html_tag_attribute_name === $wildcard_attribute_name) {
            continue;
          }
          // If a concrete attribute restriction (f.e. `data-foo`, `data-bar`,
          // etc.) exists whose attribute value restrictions are the same as the
          // wildcard attribute value restrictions (f.e. `data-*`), we must
          // explicitly drop the concrete attribute restriction in favor of the
          // wildcard one.
          if ($html_tag_attribute_restrictions === $tag_config[$wildcard_attribute_name] && preg_match($regex, $html_tag_attribute_name) === 1) {
            unset($tag_config[$html_tag_attribute_name]);
          }
        }
        $union[$tag] = $tag_config;
      }
    }

    return new self($union);
  }

  /**
   * Applies an operation (difference/intersection/union) with wildcard support.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $a
   *   The first operand.
   * @param \Drupal\ckeditor5\HTMLRestrictions $b
   *   The second operand.
   * @param string $operation_method_name
   *   The name of the private method on this class to use as the operation.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The result of the operation.
   */
  private static function applyOperation(HTMLRestrictions $a, HTMLRestrictions $b, string $operation_method_name): HTMLRestrictions {
    // 1. Operation applied to wildcard tags that exist in both operands.
    // For example: <$text-container id> in both operands.
    $a_wildcard = $a->getWildcardSubset();
    $b_wildcard = $b->getWildcardSubset();
    $wildcard_op_result = $a_wildcard->$operation_method_name($b_wildcard);

    // Early return if both operands contain only wildcard tags.
    if (count($a_wildcard->elements) === count($a->elements) && count($b_wildcard->elements) === count($b->elements)) {
      return $wildcard_op_result;
    }

    // 2. Operation applied with wildcard tags resolved into concrete tags.
    // For example: <p class="text-align-center"> in the first operand and
    // <$text-container class="text-align-center"> in the second
    // operand.
    $a_concrete = self::resolveWildcards($a);
    $b_concrete = self::resolveWildcards($b);
    $concrete_op_result = $a_concrete->$operation_method_name($b_concrete);

    // Using the PHP array union operator is safe because the two operation
    // result arrays ensure there is no overlap between the array keys.
    // @codingStandardsIgnoreStart
    assert(Inspector::assertAll(function ($t) { return self::isWildcardTag($t); }, array_keys($wildcard_op_result->elements)));
    assert(Inspector::assertAll(function ($t) { return !self::isWildcardTag($t); }, array_keys($concrete_op_result->elements)));
    // @codingStandardsIgnoreEnd

    return new self($concrete_op_result->elements + $wildcard_op_result->elements);
  }

  /**
   * Checks whether the given attribute name contains a wildcard, e.g. `data-*`.
   *
   * @param string $attribute_name
   *   The attribute name to check.
   *
   * @return bool
   *   Whether the given attribute name contains a wildcard.
   */
  private static function isWildcardAttributeName(string $attribute_name): bool {
    // @see ::validateAllowedRestrictionsPhase3()
    assert($attribute_name !== '*');
    return strpos($attribute_name, '*') !== FALSE;
  }

  /**
   * Computes a regular expression for matching a wildcard attribute name.
   *
   * @param string $wildcard_attribute_name
   *   The wildcard attribute name for which to compute a regular expression.
   *
   * @return string
   *   The computed regular expression.
   */
  private static function getRegExForWildCardAttributeName(string $wildcard_attribute_name): string {
    assert(self::isWildcardAttributeName($wildcard_attribute_name));
    return '/^' . str_replace('*', '.*', $wildcard_attribute_name) . '$/';
  }

  /**
   * Gets the subset of allowed elements whose tags are wildcards.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The subset of the given set of HTML restrictions.
   */
  public function getWildcardSubset(): HTMLRestrictions {
    return new self(array_filter($this->elements, [__CLASS__, 'isWildcardTag'], ARRAY_FILTER_USE_KEY));
  }

  /**
   * Gets the subset of allowed elements whose tags are concrete.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The subset of the given set of HTML restrictions.
   */
  public function getConcreteSubset(): HTMLRestrictions {
    return new self(array_filter($this->elements, function (string $tag_name) {
      return !self::isWildcardTag($tag_name);
    }, ARRAY_FILTER_USE_KEY));
  }

  /**
   * Gets the subset of plain tags (no attributes) from allowed elements.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The subset of the given set of HTML restrictions.
   */
  public function getPlainTagsSubset(): HTMLRestrictions {
    // This implicitly excludes wildcard tags and the global attribute `*` tag
    // because they always have attributes specified.
    return new self(array_filter($this->elements, function ($value) {
      return $value === FALSE;
    }));
  }

  /**
   * Extracts the subset of plain tags (attributes omitted) from allowed elements.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The extracted subset of the given set of HTML restrictions.
   */
  public function extractPlainTagsSubset(): HTMLRestrictions {
    // Ignore the global attribute `*` HTML tag: that is by definition not a
    // plain tag.
    $plain_tags = array_diff(array_keys($this->getConcreteSubset()->getAllowedElements()), ['*']);
    return new self(array_fill_keys($plain_tags, FALSE));
  }

  /**
   * Checks whether given tag is a wildcard.
   *
   * @param string $tag_name
   *   A tag name.
   *
   * @return bool
   *   TRUE if it is a wildcard, otherwise FALSE.
   */
  private static function isWildcardTag(string $tag_name): bool {
    return substr($tag_name, 0, 1) === '$' && array_key_exists($tag_name, self::WILDCARD_ELEMENT_METHODS);
  }

  /**
   * Resolves the wildcard tags (this consumes the wildcard tags).
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $r
   *   A set of HTML restrictions.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The concrete interpretation of the given set of HTML restrictions. All
   *   wildcard tag restrictions are resolved into restrictions on concrete
   *   elements, if concrete elements are allowed that correspond to the
   *   wildcard tags.
   *
   * @see ::getWildcardTags()
   */
  private static function resolveWildcards(HTMLRestrictions $r): HTMLRestrictions {
    // Start by resolving the wildcards in a naive, simple way: generate
    // tags, attributes and attribute values they support.
    $naively_resolved_wildcard_elements = [];
    foreach ($r->elements as $tag_name => $tag_config) {
      if (self::isWildcardTag($tag_name)) {
        $wildcard_tags = self::getWildcardTags($tag_name);
        // Do not resolve to all tags supported by the wildcard tag, but only
        // those which are explicitly supported. Because wildcard tags only
        // allow declaring support for additional attributes and attribute
        // values on already supported tags.
        foreach ($wildcard_tags as $wildcard_tag) {
          if (isset($r->elements[$wildcard_tag])) {
            $naively_resolved_wildcard_elements[$wildcard_tag] = $tag_config;
          }
        }
      }
    }
    $naive_resolution = new self($naively_resolved_wildcard_elements);

    // Now merge the naive resolution's elements with the original elements, to
    // let ::merge() pick the most permissive one.
    // This is necessary because resolving wildcards may result in concrete tags
    // becoming either more permissive:
    // - if $r is `<p> <$text-container class="foo">`
    // - then $naive will be `<p class="foo">`
    // - merging them yields `<p class="foo"> <$text-container class="foo">`
    // - diffing the wildcard subsets yields just `<p class="foo">`
    // Or it could result in concrete tags being unaffected by the resolved
    // wildcards:
    // - if $r is `<p class> <$text-container class="foo">`
    // - then $naive will be `<p class="foo">`
    // - merging them yields `<p class> <$text-container class="foo">`
    //   again
    // - diffing the wildcard subsets yields just `<p class>`
    return $r->merge($naive_resolution)->doDiff($r->getWildcardSubset());
  }

  /**
   * Gets allowed elements.
   *
   * @param bool $resolve_wildcards
   *   (optional) Whether to resolve wildcards. Defaults to TRUE. When set to
   *   FALSE, the raw allowed elements will be returned (with no processing
   *   applied hence no resolved wildcards).
   *
   * @return array
   *
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  public function getAllowedElements(bool $resolve_wildcards = TRUE): array {
    if ($resolve_wildcards) {
      return self::resolveWildcards($this)->elements;
    }

    return $this->elements;
  }

  /**
   * Transforms into the CKEditor 5 package metadata "elements" representation.
   *
   * @return string[]
   *   A list of strings, with each string expressing an allowed element,
   *   structured in the way expected by the CKEditor 5 package metadata.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/contributing/package-metadata.html
   */
  public function toCKEditor5ElementsArray(): array {
    $readable = [];
    foreach ($this->elements as $tag => $attributes) {
      $attribute_string = '';
      if (is_array($attributes)) {
        foreach ($attributes as $attribute_name => $attribute_values) {
          if (is_array($attribute_values)) {
            $attribute_values_string = implode(' ', array_keys($attribute_values));
            $attribute_string .= "$attribute_name=\"$attribute_values_string\" ";
          }
          else {
            // Special case: the global attribute `*` HTML tag.
            // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
            // @see validateAllowedRestrictionsPhase2()
            // @see validateAllowedRestrictionsPhase4()
            if ($attribute_values === FALSE) {
              assert($tag === '*');
              continue;
            }
            $attribute_string .= "$attribute_name ";
          }
        }
      }

      // Special case: the global attribute `*` HTML tag.
      // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
      // @see validateAllowedRestrictionsPhase2()
      // @see validateAllowedRestrictionsPhase4()
      if ($tag === '*' && empty(array_filter($attributes))) {
        continue;
      }

      $joined = '<' . $tag . (!empty($attribute_string) ? ' ' . trim($attribute_string) : '') . '>';
      array_push($readable, $joined);
    }
    assert(Inspector::assertAllStrings($readable));
    return $readable;
  }

  /**
   * Transforms into the Drupal HTML filter's "allowed_html" representation.
   *
   * @return string
   *   A string representing the list of allowed elements, structured in the
   *   manner expected by the "Limit allowed HTML tags and correct faulty HTML"
   *   filter plugin.
   *
   * @see \Drupal\filter\Plugin\Filter\FilterHtml
   */
  public function toFilterHtmlAllowedTagsString(): string {
    // Resolve wildcard tags, because Drupal's filter_html filter plugin does
    // not support those.
    $concrete = self::resolveWildcards($this);
    // The filter_html plugin does not allow configuring additional globally
    // allowed or disallowed attributes. It uses a hardcoded list.
    $concrete = new HTMLRestrictions(array_diff_key($concrete->getAllowedElements(FALSE), ['*' => NULL]));
    return implode(' ', $concrete->toCKEditor5ElementsArray());
  }

  /**
   * Transforms into the CKEditor 5 GHS configuration representation.
   *
   * @return string[]
   *   An array of allowed elements, structured in the manner expected by the
   *   CKEditor 5 htmlSupport plugin constructor.
   *
   * @see https://ckeditor5.github.io/docs/nightly/ckeditor5/latest/features/general-html-support.html#configuration
   * @see https://ckeditor5.github.io/docs/nightly/ckeditor5/latest/api/module_engine_view_matcher-MatcherPattern.html
   */
  public function toGeneralHtmlSupportConfig(): array {
    $allowed = [];
    // Resolve any remaining wildcards based on Drupal's assumptions on
    // wildcards to ensure all HTML tags that Drupal thinks are supported are
    // truly supported by CKEditor 5.
    $elements = self::resolveWildcards($this)->getAllowedElements();
    foreach ($elements as $tag => $attributes) {
      $to_allow = ['name' => $tag];
      assert($attributes === FALSE || is_array($attributes));
      if (is_array($attributes)) {
        foreach ($attributes as $name => $value) {
          // Convert the `'hreflang' => ['en' => TRUE, 'fr' => TRUE]` structure
          // that this class expects to the `['en', 'fr']` structure that the
          // GHS functionality in CKEditor 5 expects.
          if (is_array($value)) {
            // Ensure that all values are strings, this is necessary since PHP
            // transforms the "1" string into 1 the number when it is used as
            // an array key.
            $value = array_map('strval', array_keys($value));
          }
          // Drupal never allows style attributes due to security concerns.
          // @see \Drupal\Component\Utility\Xss
          if ($name === 'style') {
            continue;
          }
          // Special case: the global attribute `*` HTML tag.
          // @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
          // @see validateAllowedRestrictionsPhase2()
          // @see validateAllowedRestrictionsPhase4()
          assert($value === TRUE || Inspector::assertAllStrings($value) || ($tag === '*' && $value === FALSE));
          // If a single attribute value is allowed, it must be TRUE (see the
          // assertion above). Otherwise, it must be an array of strings (see
          // the assertion above), which lists all allowed attribute values. To
          // be able to configure GHS to a range of values, we need to use a
          // regular expression.
          $allowed_attribute_value = is_array($value)
            ? ['regexp' => ['pattern' => '/^(' . implode('|', str_replace('*', '.*', $value)) . ')$/']]
            : $value;
          if ($name === 'class') {
            $to_allow['classes'] = $allowed_attribute_value;
            continue;
          }
          // Most attribute restrictions specify a concrete attribute name. When
          // the attribute name contains a partial wildcard, more complex syntax
          // is needed.
          $to_allow['attributes'][] = [
            'key' => strpos($name, '*') === FALSE ? $name : ['regexp' => ['pattern' => self::getRegExForWildCardAttributeName($name)]],
            'value' => $allowed_attribute_value,
          ];
        }
      }
      $allowed[] = $to_allow;
    }

    return $allowed;
  }

  /**
   * Gets a list of CKEditor 5's `$block` text container elements.
   *
   * This is a hard coded list of known elements that CKEditor 5 uses as
   * `$block` text container elements. The elements listed here are registered
   * with `inheritAllFrom: "$block"` to the CKEditor 5 schema. This list
   * corresponds to the `$text-container` wildcard in Drupal configuration.
   *
   *
   * This group of elements is special because they allow text as an immediate
   * child node. These elements are also allowed to be used for text styles that
   * must be applied to the wrapper instead of inline to the text, such as text
   * alignment.
   *
   * This list is highly opinionated. It is based on decisions made upstream in
   * CKEditor 5. For example, `<blockquote>` is not considered as a `$block`
   * text container, meaning that text inside `<blockquote>` needs to always be
   * wrapped by an element that is `$block` text container such as `<p>`. This
   * list also excludes some special case text container elements like
   * `<caption>` that allow containing text directly inside the element, yet do
   * not fully implement the `$block` text container interface.
   *
   * It is acceptable to list the elements here because the list of elements is
   * not likely to change often. If the list changed, an upgrade path would be
   * required anyway. In most cases, missing elements would only impact new
   * functionality shipped in upstream.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/deep-dive/schema.html#generic-items
   *
   * @return string[]
   *   An array of block-level element tags.
   */
  private static function getTextContainerElementList(): array {
    return [
      'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre',
    ];
  }

  /**
   * Gets a list of all known HTML5 elements.
   *
   * @return string[]
   *   An array of HTML5 element tags.
   */
  private static function getHtml5ElementList(): array {
    return array_keys(Elements::$html5);
  }

  /**
   * Computes the tags that match the provided wildcard.
   *
   * A wildcard tag in element config is a way of representing multiple tags
   * with a single item, such as `<$text-container>` to represent CKEditor 5's
   * `$block` text container tags. Each wildcard should have a corresponding
   * callback method listed in WILDCARD_ELEMENT_METHODS that returns the set of
   * tags represented by the wildcard.
   *
   * @param string $wildcard
   *   The wildcard that represents multiple tags.
   *
   * @return string[]
   *   An array of HTML tags.
   */
  private static function getWildcardTags(string $wildcard): array {
    $wildcard_element_method = self::WILDCARD_ELEMENT_METHODS[$wildcard];
    return call_user_func([self::class, $wildcard_element_method]);
  }

}
