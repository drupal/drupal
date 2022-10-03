<?php

namespace Drupal\Core\Template;

use Drupal\Core\Site\Settings;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Default sandbox policy for Twig templates.
 *
 * Twig's sandbox extension is usually used to evaluate untrusted code by
 * limiting access to potentially unsafe properties or methods. Since we do not
 * use ViewModels when passing objects to Twig templates, we limit what those
 * objects can do by only loading certain classes, method names, and method
 * names with an allowed prefix. All object properties may be accessed.
 */
class TwigSandboxPolicy implements SecurityPolicyInterface {

  /**
   * An array of allowed methods in the form of methodName => TRUE.
   *
   * @var array
   */
  protected $allowed_methods;

  /**
   * Allowed method prefixes.
   *
   * Any method starting with one of these prefixes will be allowed.
   *
   * @var array
   */
  protected $allowed_prefixes;

  /**
   * An array of class names for which any method calls are allowed.
   *
   * @var array
   */
  protected $allowed_classes;

  /**
   * Constructs a new TwigSandboxPolicy object.
   */
  public function __construct() {
    // Allow settings.php to override our default allowed classes, methods, and
    // prefixes.
    $allowed_classes = Settings::get('twig_sandbox_allowed_classes', [
      // Allow any operations on the Attribute object as it is intended to be
      // changed from a Twig template, for example calling addClass().
      'Drupal\Core\Template\Attribute',
    ]);
    // Flip the array so we can check using isset().
    $this->allowed_classes = array_flip($allowed_classes);

    $allowed_methods = Settings::get('twig_sandbox_allowed_methods', [
      // Only allow idempotent methods.
      'id',
      'label',
      'bundle',
      'get',
      '__toString',
      'toString',
    ]);
    // Flip the array so we can check using isset().
    $this->allowed_methods = array_flip($allowed_methods);

    $this->allowed_prefixes = Settings::get('twig_sandbox_allowed_prefixes', [
      'get',
      'has',
      'is',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function checkSecurity($tags, $filters, $functions): void {}

  /**
   * {@inheritdoc}
   */
  public function checkPropertyAllowed($obj, $property): void {}

  /**
   * {@inheritdoc}
   */
  public function checkMethodAllowed($obj, $method): void {
    foreach ($this->allowed_classes as $class => $key) {
      if ($obj instanceof $class) {
        return;
      }
    }

    // Return quickly for an exact match of the method name.
    if (isset($this->allowed_methods[$method])) {
      return;
    }

    // If the method name starts with an allowed prefix, allow it. Note:
    // strpos() is between 3x and 7x faster than preg_match() in this case.
    foreach ($this->allowed_prefixes as $prefix) {
      if (strpos($method, $prefix) === 0) {
        return;
      }
    }

    throw new SecurityError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, get_class($obj)));
  }

}
