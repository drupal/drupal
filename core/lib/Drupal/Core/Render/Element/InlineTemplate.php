<?php

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element where the user supplies an in-line Twig template.
 *
 * Properties:
 * - #template: The inline Twig template used to render the element.
 * - #context: (array) The variables to substitute into the Twig template.
 *   Each variable may be a string or a render array.
 *
 * Usage example:
 * @code
 * $build['hello']  = [
 *   '#type' => 'inline_template',
 *   '#template' => "{% trans %} Hello {% endtrans %} <strong>{{name}}</strong>",
 *   '#context' => [
 *     'name' => $name,
 *   ]
 * ];
 * @endcode
 *
 * @RenderElement("inline_template")
 */
class InlineTemplate extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderInlineTemplate'],
      ],
      '#template' => '',
      '#context' => [],
    ];
  }

  /**
   * Renders a twig string directly.
   *
   * @param array $element
   *   The element.
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
