<?php

/**
 * @file
 * Contains \Drupal\text\TextProcessed.
 */

namespace Drupal\text;

use Drupal\Component\Utility\String;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for processing text with a format.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - text source: The text property containing the to be processed text.
 */
class TextProcessed extends TypedData {

  /**
   * Cached processed text.
   *
   * @var string|null
   */
  protected $processed = NULL;

  /**
   * Overrides TypedData::__construct().
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if ($definition->getSetting('text source') === NULL) {
      throw new \InvalidArgumentException("The definition's 'source' key has to specify the name of the text property to be processed.");
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue($langcode = NULL) {
    if ($this->processed !== NULL) {
      return $this->processed;
    }

    $item = $this->getParent();
    $text = $item->{($this->definition->getSetting('text source'))};

    // Avoid running check_markup() or check_plain() on empty strings.
    if (!isset($text) || $text === '') {
      $this->processed = '';
    }
    elseif ($item->getFieldDefinition()->getSetting('text_processing')) {
      $this->processed = check_markup($text, $item->format, $item->getLangcode());
    }
    else {
      // Escape all HTML and retain newlines.
      // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter
      $this->processed = nl2br(String::checkPlain($text));
    }
    return $this->processed;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value, $notify = TRUE) {
    $this->processed = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
