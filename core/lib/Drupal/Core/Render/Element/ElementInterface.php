<?php

namespace Drupal\Core\Render\Element;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for render element plugins.
 *
 * Render element plugins allow modules to declare their own Render API element
 * types and specify the default values for the properties. The values returned
 * by the getInfo() method of the element plugin will be merged with the
 * properties specified in render arrays. Thus, you can specify defaults for any
 * Render API keys, in addition to those explicitly documented by
 * \Drupal\Core\Render\ElementInfoManagerInterface::getInfo().
 *
 * Some render elements are specifically form input elements; see
 * \Drupal\Core\Render\Element\FormElementInterface for more information.
 *
 * The public API of these objects must be designed with security in mind as
 * render elements process raw user input.
 *
 * @see \Drupal\Core\Render\ElementInfoManager
 * @see \Drupal\Core\Render\Annotation\RenderElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see plugin_api
 *
 * @ingroup theme_render
 */
interface ElementInterface extends PluginInspectionInterface, RenderCallbackInterface {

  /**
   * Returns the element properties for this element.
   *
   * @return array
   *   An array of element properties. See
   *   \Drupal\Core\Render\ElementInfoManagerInterface::getInfo() for
   *   documentation of the standard properties of all elements, and the
   *   return value format.
   */
  public function getInfo();

  /**
   * Sets a form element's class attribute.
   *
   * Adds 'required' and 'error' classes as needed.
   *
   * @param array $element
   *   The form element.
   * @param array $class
   *   Array of new class names to be added.
   */
  public static function setAttributes(&$element, $class = []);

}
