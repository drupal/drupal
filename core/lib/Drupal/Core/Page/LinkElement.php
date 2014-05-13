<?php

/**
 * @file
 * Contains \Drupal\Core\Page\LinkElement.
 */

namespace Drupal\Core\Page;

/**
 * Defines a link html HEAD element, which is defined by the href of the link.
 */
class LinkElement extends HeadElement {

  /**
   * {@inheritdoc}
   */
  protected $element = 'link';

  /**
   * Constructs a new Link object.
   *
   * @param string $href
   *   The Link URI. The URI should already be processed to be a fully qualified
   *   absolute link if necessary.
   * @param string $rel
   *   (optional) The link relationship. This is usually an IANA or
   *   Microformat-defined string. Defaults to an empty string
   * @param array $attributes
   *   (optional) Additional attributes for this element. Defaults to an empty
   *   array.
   */
  public function __construct($href, $rel = '', array $attributes = array()) {
    $this->attributes = $attributes + array(
      'href' => $href,
      'rel' => $rel,
    );
  }

}
