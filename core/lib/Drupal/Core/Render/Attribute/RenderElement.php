<?php

declare(strict_types = 1);

namespace Drupal\Core\Render\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a Render element plugin attribute object.
 *
 * See \Drupal\Core\Render\Element\ElementInterface for more information
 * about render element plugins.
 *
 * Plugin Namespace: Element
 *
 * For a working example, see \Drupal\Core\Render\Element\Link.
 *
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see \Drupal\Core\Render\Element\ElementInterface
 * @see \Drupal\Core\Render\Element\RenderElementBase
 * @see \Drupal\Core\Render\Attribute\FormElement
 * @see plugin_api
 *
 * @ingroup theme_render
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RenderElement extends Plugin {
}
