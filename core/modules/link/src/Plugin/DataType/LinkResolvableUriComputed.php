<?php

namespace Drupal\link\Plugin\DataType;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\Core\Url;

/**
 * Defines a data type for a Link Resolvable URI.
 */
#[DataType(
  id: 'resolvable_uri',
  label: new TranslatableMarkup('Link Resolvable URI'),
)]
class LinkResolvableUriComputed extends Uri implements CacheableDependencyInterface {

  /**
   * The generated URL.
   *
   * @var \Drupal\Core\GeneratedUrl|null
   */
  protected $processed = NULL;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->processed !== NULL) {
      return $this->processed->getGeneratedUrl();
    }
    /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $item */
    $item = $this->getParent();
    $this->processed = $item->getUrl()->toString(TRUE);

    return $this->processed->getGeneratedUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE): void {
    if (!empty($value)) {
      $parsed = UrlHelper::parse($value);
      // If the path is not an external URL then add 'internal:' prefix to make
      // it a valid uri.
      if (strpos($parsed['path'], ':') === FALSE) {
        $parsed['path'] = 'internal:' . $parsed['path'];
      }
      $url = Url::fromUri($parsed['path'], [
        'query' => $parsed['query'],
        'fragment' => $parsed['fragment'],
      ]);
      $this->processed = $url->toString(TRUE);
    }
    else {
      $this->processed = NULL;
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $this->getValue();
    return $this->processed->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->getValue();
    return $this->processed->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $this->getValue();
    return $this->processed->getCacheMaxAge();
  }

}
