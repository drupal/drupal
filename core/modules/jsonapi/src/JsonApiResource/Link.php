<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;
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
   * @param string[] $link_relation_types
   *   An array of registered or extension RFC8288 link relation types.
   * @param array $target_attributes
   *   An associative array of target attributes for the link.
   *
   * @see https://tools.ietf.org/html/rfc8288#section-2.1
   */
  public function __construct(CacheableMetadata $cacheability, Url $url, array $link_relation_types, array $target_attributes = []) {
    // @todo: uncomment the extra assertion below when JSON:API begins to use its own extension relation types.
    assert(/* !empty($link_relation_types) && */Inspector::assertAllStrings($link_relation_types));
    assert(Inspector::assertAllStrings(array_keys($target_attributes)));
    assert(Inspector::assertAll(function ($target_attribute_value) {
      return is_string($target_attribute_value)
        || is_array($target_attribute_value)
        && Inspector::assertAllStrings($target_attribute_value);
    }, array_values($target_attributes)));
    $generated_url = $url->setAbsolute()->toString(TRUE);
    $this->href = $generated_url->getGeneratedUrl();
    $this->uri = $url;
    $this->rel = $link_relation_types;
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
   * Gets the link's relation types.
   *
   * @return string[]
   *   The link's relation types.
   */
  public function getLinkRelationTypes() {
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
   * Compares two links by their href.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link $a
   *   The first link.
   * @param \Drupal\jsonapi\JsonApiResource\Link $b
   *   The second link.
   *
   * @return int
   *   The result of strcmp() on the links' hrefs.
   */
  public static function compare(Link $a, Link $b) {
    return strcmp($a->getHref(), $b->getHref());
  }

  /**
   * Merges two link objects' relation types and target attributes.
   *
   * The links must share the same URI.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link $a
   *   The first link.
   * @param \Drupal\jsonapi\JsonApiResource\Link $b
   *   The second link.
   *
   * @return static
   *   A new JSON:API Link object with the link relation type and target
   *   attributes merged.
   */
  public static function merge(Link $a, Link $b) {
    assert(static::compare($a, $b) === 0);
    $merged_rels = array_unique(array_merge($a->getLinkRelationTypes(), $b->getLinkRelationTypes()));
    $merged_attributes = $a->getTargetAttributes();
    foreach ($b->getTargetAttributes() as $key => $value) {
      if (isset($merged_attributes[$key])) {
        // The attribute values can be either a string or an array of strings.
        $value = array_unique(array_merge(
          is_string($merged_attributes[$key]) ? [$merged_attributes[$key]] : $merged_attributes[$key],
          is_string($value) ? [$value] : $value
        ));
      }
      $merged_attributes[$key] = count($value) === 1 ? reset($value) : $value;
    }
    $merged_cacheability = (new CacheableMetadata())->addCacheableDependency($a)->addCacheableDependency($b);
    return new static($merged_cacheability, $a->getUri(), $merged_rels, $merged_attributes);
  }

}
