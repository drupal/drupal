<?php

namespace Drupal\Core\Template;

use Drupal\Core\Site\Settings;

/**
 * Default sandbox policy for Twig templates.
 *
 * Twig's sandbox extension is usually used to evaluate untrusted code by
 * limiting access to potentially unsafe properties or methods. Since we do not
 * use ViewModels when passing objects to Twig templates, we limit what those
 * objects can do by whitelisting certain classes, method names, and method
 * names with an allowed prefix. All object properties may be accessed.
 */
class TwigSandboxPolicy implements \Twig_Sandbox_SecurityPolicyInterface {

  /**
   * An array of whitelisted methods in the form of methodName => TRUE.
   */
  protected $whitelisted_methods = NULL;

  /**
   * An array of whitelisted method prefixes -- any method starting with one of
   * these prefixes will be allowed.
   */
  protected $whitelisted_prefixes = NULL;

  /**
   * An array of class names for which any method calls are allowed.
   */
  protected $whitelisted_classes = NULL;

  /**
   * Constructs a new TwigSandboxPolicy object.
   */
  public function __construct() {
    // Allow settings.php to override our default whitelisted classes, methods,
    // and prefixes.
    $whitelisted_classes = Settings::get('twig_sandbox_whitelisted_classes', [
      // Allow any operations on the Attribute object as it is intended to be
      // changed from a Twig template, for example calling addClass().
      'Drupal\Core\Template\Attribute',
    ]);
    // Flip the arrays so we can check using isset().
    $this->whitelisted_classes = array_flip($whitelisted_classes);

    $whitelisted_methods = Settings::get('twig_sandbox_whitelisted_methods', [
      // Only allow idempotent methods.
      'id',
      'label',
      'bundle',
      'get',
      '__toString',
      'toString',
    ]);
    $this->whitelisted_methods = array_flip($whitelisted_methods);

    $this->whitelisted_prefixes = Settings::get('twig_sandbox_whitelisted_prefixes', [
      'get',
      'has',
      'is',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function checkSecurity($tags, $filters, $functions) {}

  /**
   * {@inheritdoc}
   */
  public function checkPropertyAllowed($obj, $property) {}

  /**
   * {@inheritdoc}
   */
  public function checkMethodAllowed($obj, $method) {
    foreach ($this->whitelisted_classes as $class => $key) {
      if ($obj instanceof $class) {
        return TRUE;
      }
    }

    // Return quickly for an exact match of the method name.
    if (isset($this->whitelisted_methods[$method])) {
      return TRUE;
    }

    // If the method name starts with a whitelisted prefix, allow it.
    // Note: strpos() is between 3x and 7x faster than preg_match in this case.
    foreach ($this->whitelisted_prefixes as $prefix) {
      if (strpos($method, $prefix) === 0) {
        return TRUE;
      }
    }

    throw new \Twig_Sandbox_SecurityError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, get_class($obj)));
  }

}
