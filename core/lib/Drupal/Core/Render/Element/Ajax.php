<?php

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for adding Ajax to a render element.
 *
 * Holds an array whose values control the Ajax behavior of the element.
 *
 * @ingroup ajax
 *
 * @RenderElement("ajax")
 */
class Ajax extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    // By default, we don't want Ajax commands being rendered in the context of
    // an HTML page, so we don't provide defaults for #theme or #theme_wrappers.
    // However, modules can set these properties (for example, to provide an
    // HTML debugging page that displays rather than executes Ajax commands).
    return array(
      '#header' => TRUE,
      '#commands' => array(),
      '#error' => NULL,
    );
  }

}
