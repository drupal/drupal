<?php

/**
 * @file
 * Definition of Drupal\text\TextProcessed.
 */

namespace Drupal\text;

use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\TypedData\Type\String;
use Drupal\Core\TypedData\ReadOnlyException;
use InvalidArgumentException;

/**
 * A computed property for processing text with a format.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - text source: The text property containing the to be processed text.
 */
class TextProcessed extends String implements ContextAwareInterface {

  /**
   * The text property.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $text;

  /**
   * The text format property.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $format;

  /**
   * The name.
   *
   * @var string
   */
  protected $name;

  /**
   * The parent data structure.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemInterface
   */
  protected $parent;

  /**
   * Implements TypedDataInterface::__construct().
   */
  public function __construct(array $definition) {
    $this->definition = $definition;

    if (!isset($definition['settings']['text source'])) {
      throw new InvalidArgumentException("The definition's 'source' key has to specify the name of the text property to be processed.");
    }
  }

  /**
   * Implements ContextAwareInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements ContextAwareInterface::setName().
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Implements ContextAwareInterface::getParent().
   *
   * @return \Drupal\Core\Entity\Field\FieldItemInterface
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Implements ContextAwareInterface::setParent().
   */
  public function setParent($parent) {
    $this->parent = $parent;
    $this->text = $parent->get($this->definition['settings']['text source']);
    $this->format = $parent->get('format');
  }

  /**
   * Implements TypedDataInterface::getValue().
   */
  public function getValue($langcode = NULL) {

    if (!isset($this->text)) {
      throw new InvalidArgumentException('Computed properties require context for computation.');
    }

    $field = $this->parent->getParent();
    $entity = $field->getParent();
    $instance = field_info_instance($entity->entityType(), $field->getName(), $entity->bundle());

    if (!empty($instance['settings']['text_processing']) && $this->format->value) {
      return check_markup($this->text->value, $this->format->value, $entity->language()->langcode);
    }
    else {
      // If no format is available, still make sure to sanitize the text.
      return check_plain($this->text->value);
    }
  }

  /**
   * Implements TypedDataInterface::setValue().
   */
  public function setValue($value) {
    if (isset($value)) {
      throw new ReadOnlyException('Unable to set a computed property.');
    }
  }
}
