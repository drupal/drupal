<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigReferenceFunctions.
 */

namespace Drupal\Core\Template;

/**
 * A helper used to unwrap TwigReference objects transparently.
 *
 * This is providing a static magic function that makes it easier to unwrap
 * TwigReference objects and pass variables by reference to show(), hide() and
 * render().
 *
 * The problem is that twig passes variables only by value. The following is a
 * simplified version of the generated code by twig when the property "links" of
 * a render array stored in $content should be hidden:
 * @code
 * $_content_ = $content;
 * hide(getAttribute($_content_, 'links'));
 * @endcode
 * As hide() is operating on a copy of the original array the hidden property
 * is not set on the original $content variable.
 *
 * TwigReferenceFunctions can be used in combination with TwigReference to solve
 * this problem:
 * @code
 * // Internally getContextReference returns the array wrapped in a
 * // TwigReference if certain criteria are met
 * function getContextReference(&$content) {
 *   $obj = new Drupal\Core\Template\TwigReference();
 *   $obj->setReference($content);
 *   return $obj;
 * }
 *
 * // [...]
 * // Simplified, generated twig code
 * $_content_ = getContextReference($content);
 *
 * Drupal\Core\Template\TwigReferenceFunctions::hide(
 *   getAttribute($_content_, 'links')
 * );
 * @endcode
 * A TwigReference object is passed to the __callStatic function of
 * TwigReferenceFunctions. The method unwraps the TwigReference and calls the
 * hide() method essentially with a reference to $content['links'].
 *
 * Therefore the hidden property is correctly set and a successive call to
 * render() will not render the content twice.
 *
 * @see TwigReference
 * @see TwigReferenceFunction
 * @see TwigFactory
 *
 */
class TwigReferenceFunctions {

  /**
   * Magic function to call functions called from twig templates with a
   * reference to the original variable.
   *
   * This checks if the array provided by value is containing a reference to
   * the original version. If yes it replaces the argument with its reference.
   *
   * @param $name
   *   The name of the function to call.
   * @param $arguments
   *   The arguments to process and pass to the called function.
   *
   * @return mixed
   *   Returns the output of the called function.
   *
   * @see TwigReference
  */
  public static function __callStatic($name, $arguments) {
    foreach ($arguments as $key => $val) {
      if (is_object($val) && $val instanceof TwigReference) {
        $arguments[$key] = &$val->getReference();
      }
    }

    // Needed to pass by reference -- could also restrict to maximum one
    // argument instead
    $args = array();
    foreach ($arguments as $key => &$arg) {
      $args[$key] = &$arg;
    }

    return call_user_func_array($name, $args);
  }
}
