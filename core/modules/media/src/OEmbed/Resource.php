<?php

namespace Drupal\media\OEmbed;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Url;

/**
 * Value object representing an oEmbed resource.
 *
 * Data received from an oEmbed provider could be insecure. For example,
 * resources of the 'rich' type provide an HTML representation which is not
 * sanitized by this object in any way. Any values you retrieve from this object
 * should be treated as potentially dangerous user input and carefully validated
 * and sanitized before being displayed or otherwise manipulated by your code.
 *
 * Valid resource types are defined in the oEmbed specification and represented
 * by the TYPE_* constants in this class.
 *
 * @see https://oembed.com/#section2
 *
 * @internal
 *   This class is an internal part of the oEmbed system and should only be
 *   instantiated by
 *   \Drupal\media\OEmbed\ResourceFetcherInterface::fetchResource().
 */
class Resource implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * The resource type for link resources.
   */
  const TYPE_LINK = 'link';

  /**
   * The resource type for photo resources.
   */
  const TYPE_PHOTO = 'photo';

  /**
   * The resource type for rich resources.
   */
  const TYPE_RICH = 'rich';

  /**
   * The resource type for video resources.
   */
  const TYPE_VIDEO = 'video';

  /**
   * The resource type. Can be one of the static::TYPE_* constants.
   *
   * @var string
   */
  protected $type;

  /**
   * The resource provider.
   *
   * @var \Drupal\media\OEmbed\Provider
   */
  protected $provider;

  /**
   * A text title, describing the resource.
   *
   * @var string
   */
  protected $title;

  /**
   * The name of the author/owner of the resource.
   *
   * @var string
   */
  protected $authorName;

  /**
   * A URL for the author/owner of the resource.
   *
   * @var string
   */
  protected $authorUrl;

  /**
   * A URL to a thumbnail image representing the resource.
   *
   * The thumbnail must respect any maxwidth and maxheight parameters passed
   * to the oEmbed endpoint. If this parameter is present, thumbnail_width and
   * thumbnail_height must also be present.
   *
   * @var string
   *
   * @see \Drupal\media\OEmbed\UrlResolverInterface::getResourceUrl()
   * @see https://oembed.com/#section2
   */
  protected $thumbnailUrl;

  /**
   * The width of the thumbnail, in pixels.
   *
   * If this parameter is present, thumbnail_url and thumbnail_height must also
   * be present.
   *
   * @var int
   */
  protected $thumbnailWidth;

  /**
   * The height of the thumbnail, in pixels.
   *
   * If this parameter is present, thumbnail_url and thumbnail_width must also
   * be present.
   *
   * @var int
   */
  protected $thumbnailHeight;

  /**
   * The width of the resource, in pixels.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of the resource, in pixels.
   *
   * @var int
   */
  protected $height;

  /**
   * The resource URL. Only applies to 'photo' and 'link' resources.
   *
   * @var string
   */
  protected $url;

  /**
   * The HTML representation of the resource.
   *
   * Only applies to 'rich' and 'video' resources.
   *
   * @var string
   */
  protected $html;

  /**
   * Resource constructor.
   *
   * @param \Drupal\media\OEmbed\Provider $provider
   *   (optional) The resource provider.
   * @param string $title
   *   (optional) A text title, describing the resource.
   * @param string $author_name
   *   (optional) The name of the author/owner of the resource.
   * @param string $author_url
   *   (optional) A URL for the author/owner of the resource.
   * @param int $cache_age
   *   (optional) The suggested cache lifetime for this resource, in seconds.
   * @param string $thumbnail_url
   *   (optional) A URL to a thumbnail image representing the resource. If this
   *   parameter is present, $thumbnail_width and $thumbnail_height must also be
   *   present.
   * @param int $thumbnail_width
   *   (optional) The width of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_height must also be present.
   * @param int $thumbnail_height
   *   (optional) The height of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_width must also be present.
   */
  protected function __construct(?Provider $provider = NULL, $title = NULL, $author_name = NULL, $author_url = NULL, $cache_age = NULL, $thumbnail_url = NULL, $thumbnail_width = NULL, $thumbnail_height = NULL) {
    $this->provider = $provider;
    $this->title = $title;
    $this->authorName = $author_name;
    $this->authorUrl = $author_url;

    if (isset($cache_age) && is_numeric($cache_age)) {
      // If the cache age is too big, it can overflow the 'expire' column of
      // database cache backends, causing SQL exceptions. To prevent that,
      // arbitrarily limit the cache age to 5 years. That should be enough.
      $this->cacheMaxAge = Cache::mergeMaxAges((int) $cache_age, 157680000);
    }

    if ($thumbnail_url) {
      $this->thumbnailUrl = $thumbnail_url;
      $this->setThumbnailDimensions($thumbnail_width, $thumbnail_height);
    }
  }

  /**
   * Creates a link resource.
   *
   * @param string $url
   *   (optional) The URL of the resource.
   * @param \Drupal\media\OEmbed\Provider $provider
   *   (optional) The resource provider.
   * @param string $title
   *   (optional) A text title, describing the resource.
   * @param string $author_name
   *   (optional) The name of the author/owner of the resource.
   * @param string $author_url
   *   (optional) A URL for the author/owner of the resource.
   * @param int $cache_age
   *   (optional) The suggested cache lifetime for this resource, in seconds.
   * @param string $thumbnail_url
   *   (optional) A URL to a thumbnail image representing the resource. If this
   *   parameter is present, $thumbnail_width and $thumbnail_height must also be
   *   present.
   * @param int $thumbnail_width
   *   (optional) The width of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_height must also be present.
   * @param int $thumbnail_height
   *   (optional) The height of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_width must also be present.
   *
   * @return static
   */
  public static function link($url = NULL, ?Provider $provider = NULL, $title = NULL, $author_name = NULL, $author_url = NULL, $cache_age = NULL, $thumbnail_url = NULL, $thumbnail_width = NULL, $thumbnail_height = NULL) {
    $resource = new static($provider, $title, $author_name, $author_url, $cache_age, $thumbnail_url, $thumbnail_width, $thumbnail_height);
    $resource->type = self::TYPE_LINK;
    $resource->url = $url;

    return $resource;
  }

  /**
   * Creates a photo resource.
   *
   * @param string $url
   *   The URL of the photo.
   * @param int $width
   *   The width of the photo, in pixels.
   * @param int $height
   *   (optional) The height of the photo, in pixels.
   * @param \Drupal\media\OEmbed\Provider $provider
   *   (optional) The resource provider.
   * @param string $title
   *   (optional) A text title, describing the resource.
   * @param string $author_name
   *   (optional) The name of the author/owner of the resource.
   * @param string $author_url
   *   (optional) A URL for the author/owner of the resource.
   * @param int $cache_age
   *   (optional) The suggested cache lifetime for this resource, in seconds.
   * @param string $thumbnail_url
   *   (optional) A URL to a thumbnail image representing the resource. If this
   *   parameter is present, $thumbnail_width and $thumbnail_height must also be
   *   present.
   * @param int $thumbnail_width
   *   (optional) The width of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_height must also be present.
   * @param int $thumbnail_height
   *   (optional) The height of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_width must also be present.
   *
   * @return static
   */
  public static function photo($url, $width, $height = NULL, ?Provider $provider = NULL, $title = NULL, $author_name = NULL, $author_url = NULL, $cache_age = NULL, $thumbnail_url = NULL, $thumbnail_width = NULL, $thumbnail_height = NULL) {
    if (empty($url)) {
      throw new \InvalidArgumentException('Photo resources must provide a URL.');
    }

    $resource = static::link($url, $provider, $title, $author_name, $author_url, $cache_age, $thumbnail_url, $thumbnail_width, $thumbnail_height);
    $resource->type = self::TYPE_PHOTO;
    $resource->setDimensions($width, $height);

    return $resource;
  }

  /**
   * Creates a rich resource.
   *
   * @param string $html
   *   The HTML representation of the resource.
   * @param int $width
   *   The width of the resource, in pixels.
   * @param int $height
   *   (optional) The height of the resource, in pixels.
   * @param \Drupal\media\OEmbed\Provider $provider
   *   (optional) The resource provider.
   * @param string $title
   *   (optional) A text title, describing the resource.
   * @param string $author_name
   *   (optional) The name of the author/owner of the resource.
   * @param string $author_url
   *   (optional) A URL for the author/owner of the resource.
   * @param int $cache_age
   *   (optional) The suggested cache lifetime for this resource, in seconds.
   * @param string $thumbnail_url
   *   (optional) A URL to a thumbnail image representing the resource. If this
   *   parameter is present, $thumbnail_width and $thumbnail_height must also be
   *   present.
   * @param int $thumbnail_width
   *   (optional) The width of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_height must also be present.
   * @param int $thumbnail_height
   *   (optional) The height of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_width must also be present.
   *
   * @return static
   */
  public static function rich($html, $width, $height = NULL, ?Provider $provider = NULL, $title = NULL, $author_name = NULL, $author_url = NULL, $cache_age = NULL, $thumbnail_url = NULL, $thumbnail_width = NULL, $thumbnail_height = NULL) {
    if (empty($html)) {
      throw new \InvalidArgumentException('The resource must provide an HTML representation.');
    }

    $resource = new static($provider, $title, $author_name, $author_url, $cache_age, $thumbnail_url, $thumbnail_width, $thumbnail_height);
    $resource->type = self::TYPE_RICH;
    $resource->html = $html;
    $resource->setDimensions($width, $height);

    return $resource;
  }

  /**
   * Creates a video resource.
   *
   * @param string $html
   *   The HTML required to display the video.
   * @param int $width
   *   The width of the video, in pixels.
   * @param int $height
   *   (optional) The height of the video, in pixels.
   * @param \Drupal\media\OEmbed\Provider $provider
   *   (optional) The resource provider.
   * @param string $title
   *   (optional) A text title, describing the resource.
   * @param string $author_name
   *   (optional) The name of the author/owner of the resource.
   * @param string $author_url
   *   (optional) A URL for the author/owner of the resource.
   * @param int $cache_age
   *   (optional) The suggested cache lifetime for this resource, in seconds.
   * @param string $thumbnail_url
   *   (optional) A URL to a thumbnail image representing the resource. If this
   *   parameter is present, $thumbnail_width and $thumbnail_height must also be
   *   present.
   * @param int $thumbnail_width
   *   (optional) The width of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_height must also be present.
   * @param int $thumbnail_height
   *   (optional) The height of the thumbnail, in pixels. If this parameter is
   *   present, $thumbnail_url and $thumbnail_width must also be present.
   *
   * @return static
   */
  public static function video($html, $width, $height = NULL, ?Provider $provider = NULL, $title = NULL, $author_name = NULL, $author_url = NULL, $cache_age = NULL, $thumbnail_url = NULL, $thumbnail_width = NULL, $thumbnail_height = NULL) {
    $resource = static::rich($html, $width, $height, $provider, $title, $author_name, $author_url, $cache_age, $thumbnail_url, $thumbnail_width, $thumbnail_height);
    $resource->type = self::TYPE_VIDEO;

    return $resource;
  }

  /**
   * Returns the resource type.
   *
   * @return string
   *   The resource type. Will be one of the self::TYPE_* constants.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns the title of the resource.
   *
   * @return string|null
   *   The title of the resource, if known.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Returns the name of the resource author.
   *
   * @return string|null
   *   The name of the resource author, if known.
   */
  public function getAuthorName() {
    return $this->authorName;
  }

  /**
   * Returns the URL of the resource author.
   *
   * @return \Drupal\Core\Url|null
   *   The absolute URL of the resource author, or NULL if none is provided.
   */
  public function getAuthorUrl() {
    return $this->authorUrl ? Url::fromUri($this->authorUrl)->setAbsolute() : NULL;
  }

  /**
   * Returns the resource provider, if known.
   *
   * @return \Drupal\media\OEmbed\Provider|null
   *   The resource provider, or NULL if the provider is not known.
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Returns the URL of the resource's thumbnail image.
   *
   * @return \Drupal\Core\Url|null
   *   The absolute URL of the thumbnail image, or NULL if there isn't one.
   */
  public function getThumbnailUrl() {
    return $this->thumbnailUrl ? Url::fromUri($this->thumbnailUrl)->setAbsolute() : NULL;
  }

  /**
   * Returns the width of the resource's thumbnail image.
   *
   * @return int|null
   *   The thumbnail width in pixels, or NULL if there is no thumbnail.
   */
  public function getThumbnailWidth() {
    return $this->thumbnailWidth;
  }

  /**
   * Returns the height of the resource's thumbnail image.
   *
   * @return int|null
   *   The thumbnail height in pixels, or NULL if there is no thumbnail.
   */
  public function getThumbnailHeight() {
    return $this->thumbnailHeight;
  }

  /**
   * Returns the width of the resource.
   *
   * @return int|null
   *   The width of the resource in pixels, or NULL if the resource has no
   *   width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Returns the height of the resource.
   *
   * @return int|null
   *   The height of the resource in pixels, or NULL if the resource has no
   *   height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Returns the URL of the resource. Only applies to 'photo' resources.
   *
   * @return \Drupal\Core\Url|null
   *   The resource URL, if it has one.
   */
  public function getUrl() {
    if ($this->url) {
      return Url::fromUri($this->url)->setAbsolute();
    }
    return NULL;
  }

  /**
   * Returns the HTML representation of the resource.
   *
   * Only applies to 'rich' and 'video' resources.
   *
   * @return string|null
   *   The HTML representation of the resource, if it has one.
   */
  public function getHtml() {
    return isset($this->html) ? (string) $this->html : NULL;
  }

  /**
   * Sets the thumbnail dimensions.
   *
   * @param int $width
   *   The width of the resource.
   * @param int $height
   *   The height of the resource.
   *
   * @throws \InvalidArgumentException
   *   If either $width or $height are not numbers greater than zero.
   */
  protected function setThumbnailDimensions($width, $height) {
    $width = (int) $width;
    $height = (int) $height;

    if ($width > 0 && $height > 0) {
      $this->thumbnailWidth = $width;
      $this->thumbnailHeight = $height;
    }
    else {
      throw new \InvalidArgumentException('The thumbnail dimensions must be numbers greater than zero.');
    }
  }

  /**
   * Sets the dimensions.
   *
   * @param int|null $width
   *   The width of the resource.
   * @param int|null $height
   *   The height of the resource.
   *
   * @throws \InvalidArgumentException
   *   If either $width or $height are not numbers greater than zero.
   */
  protected function setDimensions($width, $height) {
    if ((isset($width) && $width <= 0) || (isset($height) && $height <= 0)) {
      throw new \InvalidArgumentException('The dimensions must be NULL or numbers greater than zero.');
    }
    $this->width = isset($width) ? (int) $width : NULL;
    $this->height = isset($height) ? (int) $height : NULL;
  }

}
