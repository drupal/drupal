<?php

namespace Drupal\Core\Render;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Render\Element\ElementInterface;
use Drupal\Core\Render\Element\Form;

/**
 * Collects available render array element types.
 */
interface ElementInfoManagerInterface extends DiscoveryInterface, FactoryInterface {

  /**
   * Retrieves the default properties for the defined element type.
   *
   * Each of the element types defined by this hook is assumed to have a
   * matching theme hook, which should be registered with hook_theme() as
   * normal.
   *
   * For more information about custom element types see the explanation at
   * https://www.drupal.org/node/169815.
   *
   * @param string $type
   *   The machine name of an element type plugin.
   *
   * @return array
   *   An associative array describing the element type being defined. The
   *   array has a number of possible attributes:
   *   - #input: boolean indicating whether this element carries a value
   *     (even if it's hidden).
   *   - #process: array of callback functions taking $element, $form_state,
   *     and $complete_form.
   *   - #after_build: array of callables taking $element and $form_state.
   *   - #validate: array of callback functions taking $form and $form_state.
   *   - #element_validate: array of callback functions taking $element and
   *     $form_state.
   *   - #pre_render: array of callables taking $element.
   *   - #post_render: array of callables taking $children and $element.
   *   - #submit: array of callback functions taking $form and $form_state.
   *   - #title_display: optional string indicating if and how #title should be
   *     displayed (see form-element.html.twig).
   *
   * @see \Drupal\Core\Render\Element\ElementInterface
   * @see \Drupal\Core\Render\Element\ElementInterface::getInfo()
   */
  public function getInfo($type);

  /**
   * Retrieves a single property for the defined element type.
   *
   * @param string $type
   *   An element type as defined by an element plugin.
   * @param string $property_name
   *   The property within the element type that should be returned.
   * @param mixed|null $default
   *   (Optional) The value to return if the element type does not specify a
   *   value for the property. Defaults to NULL.
   *
   * @return mixed
   *   The property value of the defined element type. Or the provided
   *   default value, which can be NULL.
   */
  public function getInfoProperty($type, $property_name, $default = NULL);

  /**
   * Creates a render object from a render array.
   *
   * @param \Drupal\Core\Render\Element\ElementInterface|array $element
   *   A render array or render objects. The latter is returned unchanged.
   * @param class-string<T> $class
   *   The class of the render object being created.
   *
   * @return T
   *   A render object.
   *
   * @template T of ElementInterface
   */
  public function fromRenderable(ElementInterface|array &$element, string $class = Form::class): ElementInterface;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   *   A fully configured render object.
   */
  public function createInstance($plugin_id, array $configuration = []): ElementInterface;

  /**
   * Creates a render object based on the provided class and configuration.
   *
   * @param class-string<T> $class
   *   The class of the render object being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the render object.
   *
   * @return T
   *   A fully configured render object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the render object cannot be created, such as if the class is invalid.
   *
   * @template T of ElementInterface
   */
  public function fromClass(string $class, array $configuration = []): ElementInterface;

  /**
   * Get the plugin ID from the class.
   *
   * Whenever possible, use the class type inference. Calling this method
   * should not be necessary.
   *
   * @param string $class
   *   The class of an element object.
   *
   * @return ?string
   *   The plugin ID or null if not found.
   *
   * @internal
   */
  public function getIdFromClass(string $class): ?string;

}
