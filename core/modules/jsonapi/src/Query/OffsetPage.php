<?php

namespace Drupal\jsonapi\Query;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;

/**
 * Value object for containing the requested offset and page parameters.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class OffsetPage {

  /**
   * The JSON:API pagination key name.
   *
   * @var string
   */
  const KEY_NAME = 'page';

  /**
   * The offset key in the page parameter: page[offset].
   *
   * @var string
   */
  const OFFSET_KEY = 'offset';

  /**
   * The size key in the page parameter: page[limit].
   *
   * @var string
   */
  const SIZE_KEY = 'limit';

  /**
   * Default offset.
   *
   * @var int
   */
  const DEFAULT_OFFSET = 0;

  /**
   * Max size.
   *
   * @var int
   */
  const SIZE_MAX = 50;

  /**
   * The offset for the query.
   *
   * @var int
   */
  protected $offset;

  /**
   * The size of the query.
   *
   * @var int
   */
  protected $size;

  /**
   * Instantiates an OffsetPage object.
   *
   * @param int $offset
   *   The query offset.
   * @param int $size
   *   The query size limit.
   */
  public function __construct($offset, $size) {
    $this->offset = $offset;
    $this->size = $size;
  }

  /**
   * Returns the current offset.
   *
   * @return int
   *   The query offset.
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * Returns the page size.
   *
   * @return int
   *   The requested size of the query result.
   */
  public function getSize() {
    return $this->size;
  }

  /**
   * Creates an OffsetPage object from a query parameter.
   *
   * @param mixed $parameter
   *   The `page` query parameter from the Symfony request object.
   *
   * @return static
   *   An OffsetPage object with defaults.
   */
  public static function createFromQueryParameter($parameter) {
    if (!is_array($parameter)) {
      $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:page']);
      throw new CacheableBadRequestHttpException($cacheability, 'The page parameter needs to be an array.');
    }

    $expanded = $parameter + [
      static::OFFSET_KEY => static::DEFAULT_OFFSET,
      static::SIZE_KEY => static::SIZE_MAX,
    ];

    if ($expanded[static::SIZE_KEY] > static::SIZE_MAX) {
      $expanded[static::SIZE_KEY] = static::SIZE_MAX;
    }

    return new static($expanded[static::OFFSET_KEY], $expanded[static::SIZE_KEY]);
  }

}
