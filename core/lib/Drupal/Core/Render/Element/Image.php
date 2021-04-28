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

  public function setUri(string $uri) {
    return $this->set('uri', $uri);
  }

  public function setWidth(int $width) {
    return $this->set('width', $width);
  }

  public function setHeight(int $height) {
    return $this->set('height', $height);
  }

  public function setAlt($alt) {
    return $this->set('alt', $alt);
  }

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
