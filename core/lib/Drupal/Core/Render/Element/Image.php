<?php

namespace Drupal\Core\Render\Element;

class Image extends RenderElement {

  use ElementAttributesTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'image',
    ];
  }

  /**
   * Sets the URI of the image.
   *
   * @param string $uri
   *   The URI of the image.
   *
   * @return $this
   */
  public function setUri(string $uri) {
    return $this->set('uri', $uri);
  }

  /**
   * Sets the width of the image.
   *
   * @param int $width
   *   The width of the image in pixels.
   *
   * @return $this
   */
  public function setWidth(int $width) {
    return $this->set('width', $width);
  }

  /**
   * Sets the height of the image.
   *
   * @param int $height
   *   The height of the image in pixels.
   *
   * @return $this
   */
  public function setHeight(int $height) {
    return $this->set('height', $height);
  }

  /**
   * Sets the alt text of the image.
   *
   * @param string $alt
   *   The alt text of the image.
   *
   * @return $this
   */
  public function setAlt($alt) {
    return $this->set('alt', $alt);
  }

  /**
   * Sets the title of the image.
   *
   * @param string $title
   *   The title of the image.
   *
   * @return $this
   */
  public function setTitle($title) {
    return $this->set('title', $title);
  }

  public function setSizes($sizes) {
    return $this->set('sizes', $sizes);
  }

  public function setSrcSet($srcset) {
    return $this->set('srcset', $srcset);
  }

  public function setImageStyleName(string $image_style_name) {
    return $this->set('style_name', $image_style_name);
  }

}
