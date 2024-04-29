<?php

declare(strict_types=1);

namespace Drupal\Core\Form;

/**
 * Defines an interface for forms that can be workspace-safe.
 *
 * This interface should be used by forms that have to determine whether they're
 * workspace-safe based on dynamic criteria.
 *
 * @see \Drupal\Core\Form\WorkspaceSafeFormInterface
 */
interface WorkspaceDynamicSafeFormInterface {

  /**
   * Determines whether the form is safe to be submitted in a workspace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if the form is workspace-safe, FALSE otherwise.
   */
  public function isWorkspaceSafeForm(array $form, FormStateInterface $form_state): bool;

}
