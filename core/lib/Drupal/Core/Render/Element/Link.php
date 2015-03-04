<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Link.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;

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
   *   A structured array whose keys form the arguments to _l():
   *   - #title: The link text to pass as argument to _l().
   *   - #url: The URL info either pointing to a route or a non routed path.
   *   - #options: (optional) An array of options to pass to _l() or the link
   *     generator.
   *
   * @return array
   *   The passed-in element containing a rendered link in '#markup'.
   */
  public static function preRenderLink($element) {
    // By default, link options to pass to _l() are normally set in #options.
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
        $element['#id'] = $element['#options']['attributes']['id'] = Html::getUniqueId('ajax-link');
      }
      $element = static::preRenderAjaxForm($element);
    }

    if (!empty($element['#url'])) {
      $options = NestedArray::mergeDeep($element['#url']->getOptions(), $element['#options']);
      $element['#markup'] = \Drupal::l($element['#title'], $element['#url']->setOptions($options));
    }
    return $element;
  }

}
