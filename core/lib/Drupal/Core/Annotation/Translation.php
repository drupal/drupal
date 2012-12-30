<?php

/**
 * @file
 * Definition of Drupal\Core\Annotation\Translation.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\AnnotationInterface;

/**
 * Defines a translatable annotation object.
 *
 * Some metadata within an annotation needs to be translatable. This class
 * supports that need by allowing both the translatable string and, if
 * specified, a context for that string. This class is essentially a wrapper
 * around the traditional t() function in drupal.
 *
 * @Annotation
 */
class Translation implements AnnotationInterface {

  /**
   * The translation of the value passed to the constructor of the class.
   *
   * @var string
   */
  protected $translation;

  /**
   * Constructs a Translation object.
   *
   * Parses values passed into this class through the t() function in Drupal and
   * handles an optional context for the string.
   */
  public function __construct($values) {
    $string = $values['value'];
    $options = array();
    if (!empty($values['context'])) {
      $options = array(
        'context' => $values['context'],
      );
    }
    $this->translation = t($string, array(), $options);
  }

  /**
   * Implements Drupal\Core\Annotation\AnnotationInterface::get().
   */
  public function get() {
    return $this->translation;
  }

}
