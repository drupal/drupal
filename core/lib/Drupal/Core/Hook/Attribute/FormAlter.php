<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

use Drupal\Core\Hook\Order\OrderInterface;

/**
 * Hook attribute for FormAlter.
 *
 * @see hook_form_alter().
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class FormAlter extends Hook {

  /**
   * {@inheritdoc}
   */
  public const string PREFIX = 'form';

  /**
   * {@inheritdoc}
   */
  public const string SUFFIX = 'alter';

  /**
   * Constructs a FormAlter attribute object.
   *
   * @param string $form_id
   *   (optional) The ID of the form that this implementation alters.
   *   If this is left blank then `form_alter` is the hook that is registered.
   * @param string $method
   *   (optional) The method name. If this attribute is on a method, this
   *   parameter is not required. If this attribute is on a class and this
   *   parameter is omitted, the class must have an __invoke() method, which is
   *   taken as the hook implementation.
   * @param string|null $module
   *   (optional) The module this implementation is for. This allows one module
   *   to implement a hook on behalf of another module. Defaults to the module
   *   the implementation is in.
   * @param \Drupal\Core\Hook\Order\OrderInterface|null $order
   *   (optional) Set the order of the implementation.
   */
  public function __construct(
    string $form_id = '',
    public string $method = '',
    public ?string $module = NULL,
    public ?OrderInterface $order = NULL,
  ) {
    parent::__construct($form_id, $method, $module, $order);
  }

}
