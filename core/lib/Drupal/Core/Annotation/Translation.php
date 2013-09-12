<?php

/**
 * @file
 * Definition of Drupal\Core\Annotation\Translation.
 */

namespace Drupal\Core\Annotation;

use Drupal\Component\Annotation\AnnotationInterface;

/**
 * @defgroup plugin_translatable Translatable plugin metadata
 *
 * @{
 * When providing plugin annotation, properties whose values are displayed in
 * the user interface should be made translatable. Much the same as how user
 * interface text elsewhere is wrapped in t() to make it translatable, in plugin
 * annotation, wrap translatable strings in the @ Translation() annotation.
 * For example:
 * @code
 *   title = @ Translation("Title of the plugin"),
 * @endcode
 * Remove spaces after @ in your actual plugin - these are put into this sample
 * code so that it is not recognized as annotation.
 *
 * You will also need to make sure that your class file includes the line:
 * @code
 *   use Drupal\Core\Annotation\Translation;
 * @endcode
 *
 * It is also possible to provide a context with the text, similar to t():
 * @code
 *   title = @ Translation("Bundle", context = "Validation"),
 * @endcode
 * Other t() arguments like language code are not valid to pass in. Only
 * context is supported.
 * @}
 */

/**
 * Defines a translatable annotation object.
 *
 * Some metadata within an annotation needs to be translatable. This class
 * supports that need by allowing both the translatable string and, if
 * specified, a context for that string. The string (with optional context)
 * is passed into t().
 *
 * @Annotation
 *
 * @ingroup plugin_translatable
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
