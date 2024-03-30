<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a render element for adding Ajax to a render element.
 *
 * Holds an array whose values control the Ajax behavior of the element.
 *
 * @ingroup ajax
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Return an
 *   \Drupal\Core\Ajax\AjaxResponse instead.
 *
 * @see https://www.drupal.org/node/3068104
 */
#[RenderElement('ajax')]
class Ajax extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    @trigger_error('\Drupal\Core\Render\Element\Ajax is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Return an \Drupal\Core\Ajax\AjaxResponse instead. See https://www.drupal.org/node/3068104', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    // By default, we don't want Ajax commands being rendered in the context of
    // an HTML page, so we don't provide defaults for #theme or #theme_wrappers.
    // However, modules can set these properties (for example, to provide an
    // HTML debugging page that displays rather than executes Ajax commands).
    return [
      '#header' => TRUE,
      '#commands' => [],
      '#error' => NULL,
    ];
  }

}
