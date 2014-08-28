<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\InlineTemplate.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element where the user supplies an in-line Twig template.
 *
 * @RenderElement("inline_template")
 */
class InlineTemplate extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#pre_render' => array(
        array($class, 'preRenderInlineTemplate'),
      ),
      '#template' => '',
      '#context' => array(),
    );
  }

  /**
   * Renders a twig string directly.
   *
   * @param array $element
   *
   * @return array
   */
  public static function preRenderInlineTemplate($element) {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');
    $markup = $environment->renderInline($element['#template'], $element['#context']);
    $element['#markup'] = $markup;
    return $element;
  }

}
