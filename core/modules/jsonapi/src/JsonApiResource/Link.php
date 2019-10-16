<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\DiffArray;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;

/**
 * Represents an RFC8288 based link.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see https://tools.ietf.org/html/rfc8288
 */
final class Link implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * The link URI.
   *
   * @var \Drupal\Core\Url
   */
  protected $uri;

  /**
   * The URI, as a string.
   *
   * @var string
   */
  protected $href;

  /**
   * The link relation types.
   *
   * @var string[]
   *
   * @todo: change this type documentation to be a single string in
   *   https://www.drupal.org/project/drupal/issues/3080467.
   */
  protected $rel;

  /**
   * The link target attributes.
   *
   * @var string[]
   *   An associative array where the keys are the attribute keys and values are
   *   either string or an array of strings.
   */
  protected $attributes;

  /**
   * JSON:API Link constructor.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheability
   *   Any cacheability metadata associated with the link. For example, a
   *   'call-to-action' link might reference a registration resource if an event
   *   has vacancies or a wait-list resource otherwise. Therefore, the link's
   *   cacheability might be depend on a certain entity's values other than the
   *   entity on which the link will appear.
   * @param \Drupal\Core\Url $url
   *   The Url object for the link.
   * @param string $link_relation_type
   *   An array of registered or extension RFC8288 link relation types.
   * @param array $target_attributes
   *   An associative array of target attributes for the link.
   *
   * @see https://tools.ietf.org/html/rfc8288#section-2.1
   */
  public function __construct(CacheableMetadata $cacheability, Url $url, $link_relation_type, array $target_attributes = []) {
    // @todo Remove this conditional block in drupal:9.0.0 and add a type hint to the $link_relation_type argument of this method in https://www.drupal.org/project/drupal/issues/3080467.
    if (is_array($link_relation_type)) {
      @trigger_error('Constructing a ' . self::class . ' with an array of link relation types is deprecated in drupal:8.8.0 and will throw a fatal error in drupal:9.0.0. Pass a single string instead. See https://www.drupal.org/node/3087821.', E_USER_DEPRECATED);
      assert(Inspector::assertAllStrings($link_relation_type));
    }
    else {
      assert(is_string($link_relation_type));
      $link_relation_type = [$link_relation_type];
    }
    assert(Inspector::assertAllStrings(array_keys($target_attributes)));
    assert(Inspector::assertAll(function ($target_attribute_value) {
      return is_string($target_attribute_value) || is_array($target_attribute_value);
    }, array_values($target_attributes)));
    $generated_url = $url->setAbsolute()->toString(TRUE);
    $this->href = $generated_url->getGeneratedUrl();
    $this->uri = $url;
    $this->rel = $link_relation_type;
    $this->attributes = $target_attributes;
    $this->setCacheability($cacheability->addCacheableDependency($generated_url));
  }

  /**
   * Gets the link's URI.
   *
   * @return \Drupal\Core\Url
   *   The link's URI as a Url object.
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Gets the link's URI as a string.
   *
   * @return string
   *   The link's URI as a string.
   */
  public function getHref() {
    return $this->href;
  }

  /**
   * Gets the link's relation type.
   *
   * @return string
   *   The link's relation type.
   */
  public function getLinkRelationType() {
    return reset($this->rel);
  }

  /**
   * Gets the link's relation types.
   *
   * @return string[]
   *   The link's relation types.
   *
   * @todo: remove this method in https://www.drupal.org/project/drupal/issues/3080467.
   */
  public function getLinkRelationTypes() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:8.8.0 and will be removed in drupal:9.0.0. Use getLinkRelationType() instead. See https://www.drupal.org/node/3087821.', E_USER_DEPRECATED);
    return $this->rel;
  }

  /**
   * Gets the link's target attributes.
   *
   * @return string[]
   *   The link's target attributes.
   */
  public function getTargetAttributes() {
    return $this->attributes;
  }

  /**
   * Compares two links.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link $a
   *   The first link.
   * @param \Drupal\jsonapi\JsonApiResource\Link $b
   *   The second link.
   *
   * @return int
   *   0 if the links can be considered identical, an integer greater than or
   *   less than 0 otherwise.
   */
  public static function compare(Link $a, Link $b) {
    // @todo: Remove $rel_to_string function once rel property is a single
    //   string in https://www.drupal.org/project/drupal/issues/3080467.
    $rel_to_string = function (array $rel) {
      // Sort the link relation type array so that the order of link relation
      // types does not matter during link comparison.
      sort($rel);
      return implode(' ', $rel);
    };
    // Any string concatenation would work, but a Link header-like format makes
    // it clear what is being compared.
    $a_string = sprintf('<%s>;rel="%s"', $a->getHref(), $rel_to_string($a->rel));
    $b_string = sprintf('<%s>;rel="%s"', $b->getHref(), $rel_to_string($b->rel));
    $cmp = strcmp($a_string, $b_string);
    // If the `href` or `rel` of the links are not equivalent, it's not
    // necessary to compare target attributes.
    if ($cmp === 0) {
      return (int) !empty(DiffArray::diffAssocRecursive($a->getTargetAttributes(), $b->getTargetAttributes()));
    }
    return $cmp;
  }

  /**
   * Merges two equivalent links into one link with the merged cacheability.
   *
   * The links must share the same URI, link relation type and attributes.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link $a
   *   The first link.
   * @param \Drupal\jsonapi\JsonApiResource\Link $b
   *   The second link.
   *
   * @return static
   *   A new JSON:API Link object with the cacheability of both links merged.
   */
  public static function merge(Link $a, Link $b) {
    assert(static::compare($a, $b) === 0, 'Only equivalent links can be merged.');
    $merged_cacheability = (new CacheableMetadata())->addCacheableDependency($a)->addCacheableDependency($b);
    return new static($merged_cacheability, $a->getUri(), $a->getLinkRelationType(), $a->getTargetAttributes());
  }

}
