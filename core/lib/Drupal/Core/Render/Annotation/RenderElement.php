<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Annotation\RenderElement.
 */

namespace Drupal\Core\Render\Annotation;

use Drupal\Component\Annotation\PluginID;

/**
 * Defines a render element plugin annotation object.
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
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see \Drupal\Core\Render\Annotation\FormElement
 * @see plugin_api
 *
 * @ingroup theme_render
 *
 * @Annotation
 */
class RenderElement extends PluginID {

}
