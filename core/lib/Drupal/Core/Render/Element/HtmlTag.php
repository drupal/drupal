<?php

namespace Drupal\Core\Render\Element;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;

/**
 * Provides a render element for any HTML tag, with properties and value.
 *
 * Properties:
 * - #tag: The tag name to output.
 * - #attributes: (array, optional) HTML attributes to apply to the tag. The
 *   attributes are escaped, see \Drupal\Core\Template\Attribute.
 * - #value: (string, optional) A string containing the textual contents of
 *   the tag.
 * - #noscript: (bool, optional) When set to TRUE, the markup
 *   (including any prefix or suffix) will be wrapped in a <noscript> element.
 *
 * Usage example:
 * @code
 * $build['hello'] = [
 *   '#type' => 'html_tag',
 *   '#tag' => 'p',
 *   '#value' => $this->t('Hello World'),
 * ];
 * @endcode
 *
 * @RenderElement("html_tag")
 */
class HtmlTag extends RenderElement {

  /**
   * Void elements do not contain values or closing tags.
   * @see http://www.w3.org/TR/html5/syntax.html#syntax-start-tag
   * @see http://www.w3.org/TR/html5/syntax.html#void-elements
   */
  protected static $voidElements = [
    'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
    'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr',
    'rect', 'circle', 'polygon', 'ellipse', 'stop', 'use', 'path',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderConditionalComments'],
        [$class, 'preRenderHtmlTag'],
      ],
      '#attributes' => [],
      '#value' => NULL,
    ];
  }

  /**
   * Pre-render callback: Renders a generic HTML tag with attributes.
   *
   * @param array $element
   *   An associative array containing:
   *   - #tag: The tag name to output. Typical tags added to the HTML HEAD:
   *     - meta: To provide meta information, such as a page refresh.
   *     - link: To refer to stylesheets and other contextual information.
   *     - script: To load JavaScript.
   *     The value of #tag is escaped.
   *   - #attributes: (optional) An array of HTML attributes to apply to the
   *     tag. The attributes are escaped, see \Drupal\Core\Template\Attribute.
   *   - #value: (optional) A string containing tag content, such as inline
   *     CSS. The value of #value will be XSS admin filtered if it is not safe.
   *   - #noscript: (optional) If TRUE, the markup (including any prefix or
   *     suffix) will be wrapped in a <noscript> element. (Note that passing
   *     any non-empty value here will add the <noscript> tag.)
   *
   * @return array
   */
  public static function preRenderHtmlTag($element) {
    $attributes = isset($element['#attributes']) ? new Attribute($element['#attributes']) : '';

    // An HTML tag should not contain any special characters. Escape them to
    // ensure this cannot be abused.
    $escaped_tag = HtmlUtility::escape($element['#tag']);
    $open_tag = '<' . $escaped_tag . $attributes;
    $close_tag = '</' . $escaped_tag . ">\n";
    // Construct a void element.
    if (in_array($element['#tag'], self::$voidElements)) {
      $open_tag .= ' />';
      $close_tag = "\n";
    }
    // Construct all other elements.
    else {
      $open_tag .= '>';
      $markup = $element['#value'] instanceof MarkupInterface ? $element['#value'] : Xss::filterAdmin($element['#value']);
      $element['#markup'] = Markup::create($markup);
    }
    $prefix = isset($element['#prefix']) ? $element['#prefix'] . $open_tag : $open_tag;
    $suffix = isset($element['#suffix']) ? $close_tag . $element['#suffix'] : $close_tag;
    if (!empty($element['#noscript'])) {
      $prefix = '<noscript>' . $prefix;
      $suffix .= '</noscript>';
    }
    $element['#prefix'] = Markup::create($prefix);
    $element['#suffix'] = Markup::create($suffix);
    return $element;
  }

  /**
   * Pre-render callback: Renders #browsers into #prefix and #suffix.
   *
   * @param array $element
   *   A render array with a '#browsers' property. The '#browsers' property can
   *   contain any or all of the following keys:
   *   - 'IE': If FALSE, the element is not rendered by Internet Explorer. If
   *     TRUE, the element is rendered by Internet Explorer. Can also be a string
   *     containing an expression for Internet Explorer to evaluate as part of a
   *     conditional comment. For example, this can be set to 'lt IE 7' for the
   *     element to be rendered in Internet Explorer 6, but not in Internet
   *     Explorer 7 or higher. Defaults to TRUE.
   *   - '!IE': If FALSE, the element is not rendered by browsers other than
   *     Internet Explorer. If TRUE, the element is rendered by those browsers.
   *     Defaults to TRUE.
   *   Examples:
   *   - To render an element in all browsers, '#browsers' can be left out or set
   *     to array('IE' => TRUE, '!IE' => TRUE).
   *   - To render an element in Internet Explorer only, '#browsers' can be set
   *     to array('!IE' => FALSE).
   *   - To render an element in Internet Explorer 6 only, '#browsers' can be set
   *     to array('IE' => 'lt IE 7', '!IE' => FALSE).
   *   - To render an element in Internet Explorer 8 and higher and in all other
   *     browsers, '#browsers' can be set to array('IE' => 'gte IE 8').
   *
   * @return array
   *   The passed-in element with markup for conditional comments potentially
   *   added to '#prefix' and '#suffix'.
   */
  public static function preRenderConditionalComments($element) {
    $browsers = isset($element['#browsers']) ? $element['#browsers'] : [];
    $browsers += [
      'IE' => TRUE,
      '!IE' => TRUE,
    ];

    // If rendering in all browsers, no need for conditional comments.
    if ($browsers['IE'] === TRUE && $browsers['!IE']) {
      return $element;
    }

    @trigger_error('Support for IE Conditional Comments is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3102997', E_USER_DEPRECATED);

    // Determine the conditional comment expression for Internet Explorer to
    // evaluate.
    if ($browsers['IE'] === TRUE) {
      $expression = 'IE';
    }
    elseif ($browsers['IE'] === FALSE) {
      $expression = '!IE';
    }
    else {
      // The IE expression might contain some user input data.
      $expression = Xss::filterAdmin($browsers['IE']);
    }

    // If the #prefix and #suffix properties are used, wrap them with
    // conditional comment markup. The conditional comment expression is
    // evaluated by Internet Explorer only. To control the rendering by other
    // browsers, use either the "downlevel-hidden" or "downlevel-revealed"
    // technique. See http://wikipedia.org/wiki/Conditional_comment
    // for details.

    // Ensure what we are dealing with is safe. This would be done later anyway
    // in \Drupal::service('renderer')->render().
    $prefix = isset($element['#prefix']) ? $element['#prefix'] : '';
    if ($prefix && !($prefix instanceof MarkupInterface)) {
      $prefix = Xss::filterAdmin($prefix);
    }
    $suffix = isset($element['#suffix']) ? $element['#suffix'] : '';
    if ($suffix && !($suffix instanceof MarkupInterface)) {
      $suffix = Xss::filterAdmin($suffix);
    }

    // We ensured above that $expression is either a string we created or is
    // admin XSS filtered, and that $prefix and $suffix are also admin XSS
    // filtered if they are unsafe. Thus, all these strings are safe.
    if (!$browsers['!IE']) {
      // "downlevel-hidden".
      $element['#prefix'] = Markup::create("\n<!--[if $expression]>\n" . $prefix);
      $element['#suffix'] = Markup::create($suffix . "<![endif]-->\n");
    }
    else {
      // "downlevel-revealed".
      $element['#prefix'] = Markup::create("\n<!--[if $expression]><!-->\n" . $prefix);
      $element['#suffix'] = Markup::create($suffix . "<!--<![endif]-->\n");
    }

    return $element;
  }

}
