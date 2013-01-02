<?php

/**
 * @file
 * Definition of Drupal\text\TextProcessed.
 */

namespace Drupal\text;

use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\TypedData\ContextAwareTypedData;
use Drupal\Core\TypedData\ReadOnlyException;
use InvalidArgumentException;

/**
 * A computed property for processing text with a format.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - text source: The text property containing the to be processed text.
 */
class TextProcessed extends ContextAwareTypedData {

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
   * Overrides ContextAwareTypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, ContextAwareInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if (!isset($definition['settings']['text source'])) {
      throw new InvalidArgumentException("The definition's 'source' key has to specify the name of the text property to be processed.");
    }
  }

  /**
   * Overrides ContextAwareTypedData::setContext().
   */
  public function setContext($name = NULL, ContextAwareInterface $parent = NULL) {
    parent::setContext($name, $parent);
    if (isset($parent)) {
      $this->text = $parent->get($this->definition['settings']['text source']);
      $this->format = $parent->get('format');
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue($langcode = NULL) {

    if (!isset($this->text)) {
      throw new InvalidArgumentException('Computed properties require context for computation.');
    }

    $field = $this->parent->getParent();
    $entity = $field->getParent();
    $instance = field_info_instance($entity->entityType(), $field->getName(), $entity->bundle());

    if (!empty($instance['settings']['text_processing']) && $this->format->getValue()) {
      return check_markup($this->text->getValue(), $this->format->getValue(), $entity->language()->langcode);
    }
    else {
      // If no format is available, still make sure to sanitize the text.
      return check_plain($this->text->getValue());
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value) {
    if (isset($value)) {
      throw new ReadOnlyException('Unable to set a computed property.');
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::validate().
   */
  public function validate() {
    // @todo: Implement.
  }
}
