<?php

namespace Drupal\user\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the user permissions administration form for one or more module(s).
 *
 * @internal
 */
class UserPermissionsModuleSpecificForm extends UserPermissionsForm {

  /**
   * The module list.
   *
   * A keyed array of module machine names.
   *
   * @var string[]
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  protected function permissionsByProvider(): array {
    return array_intersect_key(
      parent::permissionsByProvider(),
      array_flip($this->moduleList)
    );
  }

  /**
   * Builds the user permissions administration form for a specific module(s).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $modules
   *   (optional) One or more module machine names, comma-separated.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $modules = ''): array {
    $this->moduleList = explode(',', $modules);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Checks that at least one module defines permissions.
   *
   * @param string $modules
   *   (optional) One or more module machine names, comma-separated.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($modules): AccessResultInterface {
    foreach (explode(',', $modules) as $module) {
      if ($this->permissionHandler->moduleProvidesPermissions($module)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
