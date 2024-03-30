<?php

declare(strict_types = 1);

namespace Drupal\Core\Render\Attribute;

/**
 * Defines a form element plugin attribute object.
 *
 * See \Drupal\Core\Render\Element\FormElementInterface for more information
 * about form element plugins.
 *
 * Plugin Namespace: Element
 *
 * For a working example, see \Drupal\Core\Render\Element\Textfield.
 *
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see \Drupal\Core\Render\Element\FormElementInterface
 * @see \Drupal\Core\Render\Element\FormElementBase
 * @see \Drupal\Core\Render\Attribute\RenderElement
 * @see plugin_api
 *
 * @ingroup theme_render
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FormElement extends RenderElement {
}
