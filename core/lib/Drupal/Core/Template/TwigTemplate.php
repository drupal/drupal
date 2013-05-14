<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigTemplate.
 */

namespace Drupal\Core\Template;

/**
 * This is the base class for compiled Twig templates.
 */
abstract class TwigTemplate extends \Twig_Template {

  /**
   * A class used to pass variables by reference while they are used in Twig.
   */
  protected $twig_reference = NULL;

  /**
   * List of the name of variables to be passed around as references.
   *
   * @var array
   */
  protected $is_reference = array();

  /**
   * List of the name of variables to be passed around by value.
   *
   * @var array
   */
  protected $is_no_reference = array();

  /**
   * @param array $context
   *   The variables available to the template.
   * @param $item
   *   The name of the variable.
   * @return mixed
   *   The requested variable.
   */
  final protected function getContextReference(&$context, $item)
  {
    // Optimized version. NULL is a valid value for $context[$item], we only
    // want to error if it hasn't been defined at all.
    if (!isset($context[$item]) && !array_key_exists($item, $context)) {
      // We don't want to throw an exception, but issue a warning instead.
      // This is the easiest way to do so.
      // @todo Decide based on prod vs. dev setting
      $msg = new \Twig_Error(t('@item could not be found in _context', array('@item' => $item)));
      trigger_error($msg->getMessage(), E_USER_WARNING);
      return NULL;
    }

    // Return item instead of its reference inside a loop.
    // @todo 'hide' and 'show' are not supported inside a loop for now.
    // This should be a non-issue as soon as this lands:
    // @see http://drupal.org/node/1922304
    if (isset($context['_seq'])) {
      return $context[$item];
    }

    // The first test also finds empty / null render arrays
    if (!$context[$item] || isset($this->is_no_reference[$item])) {
      return $context[$item];
    }

    if (isset($context['_references'][$item])) {
      return $context['_references'][$item];
    }

    // @todo Check if this is a render array (existence of #theme?)
    if ((!isset($this->is_reference[$item])) && ($context[$item] instanceof \TwigMarkup || !is_array($context[$item]))) {
      $this->is_no_reference[$item] = TRUE;
      return $context[$item];
    }

    if ($this->twig_reference == NULL) {
      $this->twig_reference = new TwigReference();
    }
    $ref = clone $this->twig_reference; // clone is _much_ faster than new
    $ref->setReference($context[$item]);

    // Save that this is a reference
    $context['_references'][$item] = $ref;
    $this->is_reference[$item] = TRUE;

    return $ref;
  }
}
