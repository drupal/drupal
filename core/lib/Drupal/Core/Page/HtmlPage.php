<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlPage.
 */

namespace Drupal\Core\Page;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Template\Attribute;

/**
 * Data object for an HTML page.
 */
class HtmlPage extends HtmlFragment {

  /**
   * Attributes for the HTML element.
   *
   * @var \Drupal\Core\Template\Attribute
   */
  protected $htmlAttributes;

  /**
   * Attributes for the BODY element.
   *
   * @var \Drupal\Core\Template\Attribute
   */
  protected $bodyAttributes;

  /**
   * Auxiliary content area, above the main content.
   *
   * @var string
   */
  protected $bodyTop = '';

  /**
   * Auxiliary content area, below the main content.
   *
   * @var string
   */
  protected $bodyBottom = '';

  /**
   * The HTTP status code of this page.
   *
   * @var int
   */
  protected $statusCode = 200;

  /**
   * Constructs a new HtmlPage object.
   *
   * @param string $content
   *   (optional) The body content of the page.
   * @param array $cache_info
   *   The cache information.
   * @param string $title
   *   (optional) The title of the page.
   */
  public function __construct($content = '', array $cache_info = array(), $title = '') {
    parent::__construct($content, $cache_info);

    $this->title = $title;

    $this->htmlAttributes = new Attribute();
    $this->bodyAttributes = new Attribute();
  }

  /**
   * Returns the HTML attributes for this HTML page.
   *
   * @return \Drupal\Core\Template\Attribute
   */
  public function getHtmlAttributes() {
    return $this->htmlAttributes;
  }

  /**
   * Implodes the meta and link elements for the template.
   *
   * @return string
   *   A string of meta and link tags.
   */
  public function getHead() {
    // Each MetaElement or LinkElement is a subclass of
    // \Drupal\Core\Page\HeadElement and generates safe output when __toString()
    // is called on it. Thus, the whole concatenation is also safe.
    return SafeMarkup::set(implode("\n", $this->getMetaElements()) . implode("\n", $this->getLinkElements()));
  }

  /**
   * Returns a themed presentation of all JavaScript code for the current page.
   *
   * @param string $scope
   *   (optional) The scope for which the JavaScript rules should be returned.
   *   Defaults to 'header'.
   *
   * @return string
   *   All JavaScript code segments and includes for the scope as HTML tags.
   *
   * @see drupal_get_js()
   */
  public function getScripts($scope = 'header') {
    return drupal_get_js($scope);
  }

  /**
   * Returns a themed representation of all stylesheets to attach to the page.
   *
   * @return string
   *   A string of XHTML CSS tags.
   *
   * @see drupal_get_css()
   */
  public function getStyles() {
    return drupal_get_css();
  }

  /**
   * Returns the HTML attributes for the body element of this page.
   *
   * @return \Drupal\Core\Template\Attribute
   */
  public function getBodyAttributes() {
    return $this->bodyAttributes;
  }

  /**
   * Sets the top-content of this page.
   *
   * @param string $content
   *   The top-content to set.
   *
   * @return $this
   *   The called object.
   */
  public function setBodyTop($content) {
    $this->bodyTop = $content;
    return $this;
  }

  /**
   * Returns the top-content of this page.
   *
   * @return string
   *   The top-content of this page.
   */
  public function getBodyTop() {
    return $this->bodyTop;
  }

  /**
   * Sets the bottom-content of this page.
   *
   * @param string $content
   *   The bottom-content to set.
   *
   * @return $this
   *   The called object.
   */
  public function setBodyBottom($content) {
    $this->bodyBottom = $content;
    return $this;
  }

  /**
   * Returns the bottom-content of this page.
   *
   * @return string
   *   The bottom-content of this page.
   */
  public function getBodyBottom() {
    return $this->bodyBottom;
  }

  /**
   * Sets the HTTP status of this page.
   *
   * @param int $status
   *   The status code to set.
   *
   * @return $this
   *   The called object.
   */
  public function setStatusCode($status) {
    $this->statusCode = $status;
    return $this;
  }

  /**
   * Returns the status code of this response.
   *
   * @return int
   *   The status code of this page.
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * Sets the cache tags associated with this HTML page.
   *
   * @param array $cache_tags
   *   The cache tags associated with this HTML page.
   *
   * @return $this
   *   The called object.
   */
  public function setCacheTags(array $cache_tags) {
    $this->cache['tags'] = $cache_tags;
    return $this;
  }

  /**
   * Gets all feed links.
   *
   * @return \Drupal\Core\Page\FeedLinkElement[]
   *   A list of feed links attached to the page.
   */
  public function getFeedLinkElements() {
    $feed_links = array();
    foreach ($this->getLinkElements() as $link) {
      if ($link instanceof FeedLinkElement) {
        $feed_links[] = $link;
      }
    }
    return $feed_links;
  }

}

