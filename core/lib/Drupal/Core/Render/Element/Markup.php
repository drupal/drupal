<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Markup.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\SafeString;

/**
 * Provides a render element for HTML as a string, with sanitization.
 *
 * Properties:
 * - #markup: Specifies that the array provides HTML markup directly. Unless
 *   the markup is very simple, such as an explanation in a paragraph tag, it
 *   is normally preferable to use #theme or #type instead, so that the theme
 *   can customize the markup. Note that the value is passed through
 *   \Drupal\Component\Utility\Xss::filterAdmin(), which strips known XSS
 *   vectors while allowing a permissive list of HTML tags that are not XSS
 *   vectors. (I.e, <script> and <style> are not allowed.) See
 *   \Drupal\Component\Utility\Xss::$adminTags for the list of tags that will
 *   be allowed. If your markup needs any of the tags that are not in this
 *   whitelist, then you can implement a theme hook and template file and/or
 *   an asset library. Alternatively, you can use the render array keys
 *   #safe_strategy and #allowed_tags to alter how #markup is made safe.
 * - #safe_strategy: If #markup is supplied this can be used to change
 *   how the string is made safe for render. By default, all #markup is filtered
 *   using Xss::adminFilter(). However, if the string should be escaped using
 *   Html::escape() then this should be set to Markup::SAFE_STRATEGY_ESCAPE.
 * - #allowed_tags: If #markup is supplied this can be used to change which tags
 *   are using to filter the markup. The value should be an array of tags that
 *   Xss::filter() would accept. If #safe_strategy is set to
 *   Markup::SAFE_STRATEGY_ESCAPE this value is ignored.
 *
 * Usage example:
 * @code
 * $output['admin_filtered_string'] = array(
 *   '#type' => 'markup',
 *   '#markup' => '<em>This is filtered using the admin tag list</em>',
 * );
 * $output['filtered_string'] = array(
 *   '#type' => 'markup',
 *   '#markup' => '<em>This is filtered</em>',
 *   '#allowed_tags' => ['strong'],
 * );
 * $output['escaped_string'] = array(
 *   '#type' => 'markup',
 *   '#markup' => '<em>This is escaped</em>',
 *   '#safe_strategy' => Markup::SAFE_STRATEGY_ESCAPE,
 * );
 * @endcode
 *
 * @see theme_render
 *
 * @ingroup sanitization
 *
 * @RenderElement("markup")
 */
class Markup extends RenderElement {

  /**
   * #safe_strategy indicating #markup should be filtered.
   *
   * @see ::ensureMarkupIsSafe()
   * @see \Drupal\Component\Utility\Xss::filter()
   */
  const SAFE_STRATEGY_FILTER = 'xss';

  /**
   * #safe_strategy indicating #markup should be escaped.
   *
   * @see ::ensureMarkupIsSafe()
   * @see \Drupal\Component\Utility\Html::escape()
   */
  const SAFE_STRATEGY_ESCAPE = 'escape';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [static::class, 'ensureMarkupIsSafe'],
      ],
    ];
  }

  /**
   * Escapes or filters #markup as required.
   *
   * Drupal uses Twig's auto-escape feature to improve security. This feature
   * automatically escapes any HTML that is not known to be safe. Due to this
   * the render system needs to ensure that all markup it generates is marked
   * safe so that Twig does not do any additional escaping.
   *
   * By default all #markup is filtered to protect against XSS using the admin
   * tag list. Render arrays can alter the list of tags allowed by the filter
   * using the #allowed_tags property. This value should be an array of tags
   * that Xss::filter() would accept. Render arrays can escape #markup instead
   * of XSS filtering by setting the #safe_strategy property to
   * Markup:SAFE_STRATEGY_ESCAPE. If the escaping strategy is used #allowed_tags
   * is ignored.
   *
   * @param array $elements
   *   A render array with #markup set.
   *
   * @return \Drupal\Component\Utility\SafeStringInterface|string
   *   The escaped markup wrapped in a SafeString object. If
   *   SafeMarkup::isSafe($elements['#markup']) returns TRUE, it won't be
   *   escaped or filtered again.
   *
   * @see \Drupal\Component\Utility\Html::escape()
   * @see \Drupal\Component\Utility\Xss::filter()
   * @see \Drupal\Component\Utility\Xss::adminFilter()
   * @see \Drupal\Core\Render\Element\Markup::SAFE_STRATEGY_FILTER
   * @see \Drupal\Core\Render\Element\Markup::SAFE_STRATEGY_ESCAPE
   */
  public static function ensureMarkupIsSafe(array $elements) {
    if (empty($elements['#markup'])) {
      return $elements;
    }

    $strategy = isset($elements['#safe_strategy']) ? $elements['#safe_strategy'] : static::SAFE_STRATEGY_FILTER;
    if (SafeMarkup::isSafe($elements['#markup'])) {
      // Nothing to do as #markup is already marked as safe.
      return $elements;
    }
    elseif ($strategy == static::SAFE_STRATEGY_ESCAPE) {
      $markup = HtmlUtility::escape($elements['#markup']);
    }
    else {
      // The default behaviour is to XSS filter using the admin tag list.
      $tags = isset($elements['#allowed_tags']) ? $elements['#allowed_tags'] : Xss::getAdminTagList();
      $markup = Xss::filter($elements['#markup'], $tags);
    }

    $elements['#markup'] = SafeString::create($markup);

    return $elements;
  }

}
