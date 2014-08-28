<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Html.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\UrlHelper;

/**
 * Provides a render element for <html>.
 *
 * @RenderElement("html")
 */
class Html extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#theme' => 'html',
      '#pre_render' => array(
        array($class, 'preRenderHtml'),
      ),
      // HTML5 Shiv
      '#attached' => array(
        'library' => array('core/html5shiv'),
      ),
    );
  }

  /**
   * #pre_render callback for the html element type.
   *
   * @param array $element
   *   A structured array containing the html element type build properties.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderHtml($element) {
    // Add favicon.
    if (static::themeGetSetting('features.favicon')) {
      $favicon = static::themeGetSetting('favicon.url');
      $type = static::themeGetSetting('favicon.mimetype');
      $element['#attached']['drupal_add_html_head_link'][][] = array(
        'rel' => 'shortcut icon',
        'href' => UrlHelper::stripDangerousProtocols($favicon),
        'type' => $type,
      );
    }

    return $element;
  }

  /**
   * Wraps theme_get_setting().
   */
  protected static function themeGetSetting($setting_name) {
    return theme_get_setting($setting_name);
  }

}
