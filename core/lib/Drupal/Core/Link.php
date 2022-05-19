<?php

namespace Drupal\Core;

use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Defines an object that holds information about a link.
 */
class Link implements RenderableInterface {

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The link text for the anchor tag as a translated string or render array.
   *
   * Strings will be sanitized automatically. If you need to output HTML in
   * the link text, use a render array or an already sanitized string such as
   * the output of \Drupal\Component\Utility\Xss::filter() or
   * \Drupal\Component\Render\FormattableMarkup.
   *
   * @var string|array|\Drupal\Component\Render\MarkupInterface
   */
  protected $text;

  /**
   * The URL of the link.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * Constructs a new Link object.
   *
   * @param string|array|\Drupal\Component\Render\MarkupInterface $text
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Render\FormattableMarkup.
   * @param \Drupal\Core\Url $url
   *   The url object.
   */
  public function __construct($text, Url $url) {
    $this->text = $text;
    $this->url = $url;
  }

  /**
   * Creates a Link object from a given route name and parameters.
   *
   * @param string|array|\Drupal\Component\Render\MarkupInterface $text
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Render\FormattableMarkup.
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   The options parameter takes exactly the same structure.
   *   See \Drupal\Core\Url::fromUri() for details.
   *
   * @return static
   */
  public static function createFromRoute($text, $route_name, $route_parameters = [], $options = []) {
    return new static($text, new Url($route_name, $route_parameters, $options));
  }

  /**
   * Creates a Link object from a given Url object.
   *
   * @param string|array|\Drupal\Component\Render\MarkupInterface $text
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Render\FormattableMarkup.
   * @param \Drupal\Core\Url $url
   *   The Url to create the link for.
   *
   * @return static
   */
  public static function fromTextAndUrl($text, Url $url) {
    return new static($text, $url);
  }

  /**
   * Returns the text of the link.
   *
   * @return string|array|\Drupal\Component\Render\MarkupInterface
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Render\FormattableMarkup.
   */
  public function getText() {
    return $this->text;
  }

  /**
   * Sets the new text of the link.
   *
   * @param string|array|\Drupal\Component\Render\MarkupInterface $text
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Render\FormattableMarkup.
   *
   * @return $this
   */
  public function setText($text) {
    $this->text = $text;
    return $this;
  }

  /**
   * Returns the URL of the link.
   *
   * @return \Drupal\Core\Url
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Sets the URL of this link.
   *
   * @param Url $url
   *   The URL object to set
   *
   * @return $this
   */
  public function setUrl(Url $url) {
    $this->url = $url;
    return $this;
  }

  /**
   * Generates the HTML for this Link object.
   *
   * Do not use this method to render a link in an HTML context. In an HTML
   * context, self::toRenderable() should be used so that render cache
   * information is maintained. However, there might be use cases such as tests
   * and non-HTML contexts where calling this method directly makes sense.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The link HTML markup.
   *
   * @see \Drupal\Core\Link::toRenderable()
   */
  public function toString() {
    return $this->getLinkGenerator()->generateFromLink($this);
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    return [
      '#type' => 'link',
      '#url' => $this->url,
      '#title' => $this->text,
    ];
  }

  /**
   * Returns the link generator.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   *   The link generator
   */
  protected function getLinkGenerator() {
    if (!isset($this->linkGenerator)) {
      $this->linkGenerator = \Drupal::service('link_generator');
    }
    return $this->linkGenerator;
  }

  /**
   * Sets the link generator service.
   *
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $generator
   *   The link generator service.
   *
   * @return $this
   */
  public function setLinkGenerator(LinkGeneratorInterface $generator) {
    $this->linkGenerator = $generator;

    return $this;
  }

}
