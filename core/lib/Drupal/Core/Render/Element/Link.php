<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Link.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a link render element.
 *
 * @RenderElement("link")
 */
class Link extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#pre_render' => array(
        array($class, 'preRenderLink'),
      ),
    );
  }

  /**
   * Pre-render callback: Renders a link into #markup.
   *
   * Doing so during pre_render gives modules a chance to alter the link parts.
   *
   * @param array $element
   *   A structured array whose keys form the arguments to l():
   *   - #title: The link text to pass as argument to l().
   *   - One of the following
   *     - #route_name and (optionally) a #route_parameters array; The route
   *       name and route parameters which will be passed into the link
   *       generator.
   *     - #href: The system path or URL to pass as argument to l().
   *   - #options: (optional) An array of options to pass to l() or the link
   *     generator.
   *
   * @return array
   *   The passed-in element containing a rendered link in '#markup'.
   */
  public static function preRenderLink($element) {
    // By default, link options to pass to l() are normally set in #options.
    $element += array('#options' => array());
    // However, within the scope of renderable elements, #attributes is a valid
    // way to specify attributes, too. Take them into account, but do not override
    // attributes from #options.
    if (isset($element['#attributes'])) {
      $element['#options'] += array('attributes' => array());
      $element['#options']['attributes'] += $element['#attributes'];
    }

    // This #pre_render callback can be invoked from inside or outside of a Form
    // API context, and depending on that, a HTML ID may be already set in
    // different locations. #options should have precedence over Form API's #id.
    // #attributes have been taken over into #options above already.
    if (isset($element['#options']['attributes']['id'])) {
      $element['#id'] = $element['#options']['attributes']['id'];
    }
    elseif (isset($element['#id'])) {
      $element['#options']['attributes']['id'] = $element['#id'];
    }

    // Conditionally invoke self::preRenderAjaxForm(), if #ajax is set.
    if (isset($element['#ajax']) && !isset($element['#ajax_processed'])) {
      // If no HTML ID was found above, automatically create one.
      if (!isset($element['#id'])) {
        $element['#id'] = $element['#options']['attributes']['id'] = drupal_html_id('ajax-link');
      }
      // If #ajax['path] was not specified, use the href as Ajax request URL.
      if (!isset($element['#ajax']['path'])) {
        $element['#ajax']['path'] = $element['#href'];
        $element['#ajax']['options'] = $element['#options'];
      }
      $element = static::preRenderAjaxForm($element);
    }

    if (isset($element['#route_name'])) {
      $element['#route_parameters'] = empty($element['#route_parameters']) ? array() : $element['#route_parameters'];
      $element['#markup'] = \Drupal::linkGenerator()->generate($element['#title'], $element['#route_name'], $element['#route_parameters'], $element['#options']);
    }
    else {
      $element['#markup'] = l($element['#title'], $element['#href'], $element['#options']);
    }
    return $element;
  }

}
