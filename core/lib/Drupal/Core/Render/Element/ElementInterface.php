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
 * @see \Drupal\Core\Render\Attribute\RenderElement
 * @see \Drupal\Core\Render\Element\RenderElementBase
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
   * Initialize storage.
   *
   * This will only have an effect the first time it is called, once it has
   * been called, subsequent calls will not have an effect.
   * Only the plugin manager should ever call this method.
   *
   * @param array $element
   *   The containing element.
   *
   * @return $this
   *
   * @internal
   */
  public function initializeInternalStorage(array &$element): static;

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

  /**
   * Returns a render array.
   *
   * @param string|null $wrapper_key
   *   An optional wrapper.
   *
   * @return array|\Drupal\Core\Render\Element\ElementInterface
   *   A render array. Make sure to take the return value as a reference.
   *   If $wrapper_key is not given then the stored render element is returned.
   *   If $wrapper_key is given then [$wrapper_key => &$element] is returned.
   *   The return value is typed with array|ElementInterface to prepare for
   *   Drupal 12, where the plan for this method is to return an
   *   ElementInterface object. If that plan goes through then in Drupal 13
   *   support for render arrays will be dropped.
   */
  public function &toRenderable(?string $wrapper_key = NULL): array|ElementInterface;

  /**
   * Returns child elements.
   *
   * @return \Traversable<\Drupal\Core\Render\Element\ElementInterface>
   *   Keys will be children names, values are render objects.
   */
  public function getChildren(): \Traversable;

  /**
   * Gets a child.
   *
   * @param int|string|list<int|string> $name
   *   The name of the child. Can also be an integer. Or a list of these.
   *   It is an integer when the field API uses the delta for children.
   *
   * @return ?\Drupal\Core\Render\Element\ElementInterface
   *   The child render object.
   */
  public function getChild(int|string|array $name): ?ElementInterface;

  /**
   * Adds a child render element.
   *
   * @param int|string $name
   *   The name of the child. Can also be an integer when the child is a delta.
   * @param array|\Drupal\Core\Render\Element\ElementInterface $child
   *   A render array or a render object.
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   *   The added child as a render object.
   */
  public function addChild(int|string $name, ElementInterface|array &$child): ElementInterface;

  /**
   * Creates a render object and attaches it to the current render object.
   *
   * @param int|string $name
   *   The name of the child. Can also be an integer.
   * @param class-string<T> $class
   *   The class of the render object.
   * @param array $configuration
   *   An array of configuration relevant to the render object.
   * @param bool $copyProperties
   *   Copy properties (but not children) from the parent. This is useful for
   *   widgets for example.
   *
   * @return T
   *   The child render object.
   *
   * @template T of \Drupal\Core\Render\Element\ElementInterface
   */
  public function createChild(int|string $name, string $class, array $configuration = [], bool $copyProperties = FALSE): ElementInterface;

  /**
   * Removes a child.
   *
   * @param int|string $name
   *   The name of the child. Can also be an integer.
   *
   * @return ?\Drupal\Core\Render\Element\ElementInterface
   *   The removed render object if any, or NULL if the child could not be
   *   found.
   */
  public function removeChild(int|string $name): ?ElementInterface;

  /**
   * Change the type of the element.
   *
   * Changes only the #type all other properties and children are preserved.
   *
   * @param class-string<T> $class
   *   The class of the new render object.
   *
   * @return T
   *   The new render object.
   *
   * @template T of \Drupal\Core\Render\Element\ElementInterface
   */
  public function changeType(string $class): ElementInterface;

}
