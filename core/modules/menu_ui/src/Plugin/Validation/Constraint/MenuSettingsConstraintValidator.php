<?php

namespace Drupal\menu_ui\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\menu_ui\MenuUiUtility;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing the menu settings in pending revisions.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Menu
 *   settings validation is now handled as a form validation callback in
 *   \Drupal\menu_ui\Hook\MenuUiHooks::formNodeFormValidate().
 *
 * @see https://www.drupal.org/node/3606616
 */
class MenuSettingsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Constructs a new MenuSettingsConstraintValidator.
   *
   * @param \Drupal\menu_ui\MenuUiUtility $menuUiUtility
   *   The menu UI utility service.
   */
  public function __construct(
    protected MenuUiUtility $menuUiUtility,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    @trigger_error('MenuSettingsConstraintValidator is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Menu settings validation is now handled as a form validation callback in \Drupal\menu_ui\Hook\MenuUiHooks::formNodeFormValidate(). See https://www.drupal.org/node/3606616', E_USER_DEPRECATED);
  }

}
