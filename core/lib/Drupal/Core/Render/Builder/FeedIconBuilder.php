<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'feed_icon' element.
 */
class FeedIconBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'feed_icon'];

  /**
   * Set the url property on the feed_icon.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setUrl($value) {
    $this->set('url', $value);
    return $this;
  }

  /**
   * Set the title property on the feed_icon.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitle($value) {
    $this->set('title', $value);
    return $this;
  }

}
