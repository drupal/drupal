<?php

/**
 * @file
 * Contains \Drupal\Core\Annotation\ContextDefinition.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @defgroup plugin_context Annotation for context definition
 * @{
 * Describes how to use ContextDefinition annotation.
 *
 * When providing plugin annotations, contexts can be defined to support UI
 * interactions through providing limits, and mapping contexts to appropriate
 * plugins. Context definitions can be provided as such:
 * @code
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * @endcode
 * Remove spaces after @ in your actual plugin - these are put into this sample
 * code so that it is not recognized as an annotation.
 *
 * To add a label to a context definition use the "label" key:
 * @code
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * @endcode
 *
 * Contexts are required unless otherwise specified. To make an optional
 * context use the "required" key:
 * @code
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE, label = @Translation("Node"))
 *   }
 * @endcode
 *
 * To define multiple contexts, simply provide different key names in the
 * context array:
 * @code
 *   context = {
 *     "artist" = @ContextDefinition("entity:node", label = @Translation("Artist")),
 *     "album" = @ContextDefinition("entity:node", label = @Translation("Album"))
 *   }
 * @endcode
 *
 * @see annotation
 *
 * @}
 */

/**
 * Defines a context definition annotation object.
 *
 * Some plugins require various data contexts in order to function. This class
 * supports that need by allowing the contexts to be easily defined within an
 * annotation and return a ContextDefinitionInterface implementing class.
 *
 * @Annotation
 *
 * @ingroup plugin_context
 */
class ContextDefinition extends Plugin {

  /**
   * The ContextDefinitionInterface object.
   *
   * @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   */
  protected $definition;

  /**
   * Constructs a new context definition object.
   *
   * @param array $values
   *   An associative array with the following keys:
   *   - value: The required data type.
   *   - label: (optional) The UI label of this context definition.
   *   - required: (optional) Whether the context definition is required.
   *   - multiple: (optional) Whether the context definition is multivalue.
   *   - description: (optional) The UI description of this context definition.
   *   - class: (optional) A custom ContextDefinitionInterface class.
   *
   * @throws \Exception
   *   Thrown when the class key is specified with a non
   *   ContextDefinitionInterface implementing class.
   */
  public function __construct(array $values) {
    $values += array(
      'required' => TRUE,
      'multiple' => FALSE,
      'label' => NULL,
      'description' => NULL,
    );
    if (isset($values['class']) && !in_array('Drupal\Core\Plugin\Context\ContextDefinitionInterface', class_implements($values['class']))) {
      throw new \Exception('ContextDefinition class must implement \Drupal\Core\Plugin\Context\ContextDefinitionInterface.');
    }
    $class = isset($values['class']) ? $values['class'] : 'Drupal\Core\Plugin\Context\ContextDefinition';
    $this->definition = new $class($values['value'], $values['label'], $values['required'], $values['multiple'], $values['description']);
  }

  /**
   * Returns the value of an annotation.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   */
  public function get() {
    return $this->definition;
  }

}
