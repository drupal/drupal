<?php

namespace Drupal\workspaces\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceDynamicSafeFormInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Defines a class for reacting to form operations.
 */
class FormOperations {

  public function __construct(
    protected WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    // No alterations are needed if we're not in a workspace context.
    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return;
    }

    // If a form hasn't already been marked as safe or not to submit in a
    // workspace, check the generic interfaces.
    if (!$form_state->has('workspace_safe')) {
      $form_object = $form_state->getFormObject();
      $workspace_safe = $form_object instanceof WorkspaceSafeFormInterface
        || ($form_object instanceof WorkspaceDynamicSafeFormInterface && $form_object->isWorkspaceSafeForm($form, $form_state));

      $form_state->set('workspace_safe', $workspace_safe);
    }

    // Add a validation step for every other form.
    if ($form_state->get('workspace_safe') !== TRUE) {
      $form['workspace_safe'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
      $this->addWorkspaceValidation($form);
    }
  }

  /**
   * Adds our validation handler recursively on each element of a form.
   *
   * @param array &$element
   *   An associative array containing the structure of the form.
   */
  protected function addWorkspaceValidation(array &$element): void {
    // Recurse through all children and add our validation handler if needed.
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]) && $element[$key]) {
        $this->addWorkspaceValidation($element[$key]);
      }
    }

    if (isset($element['#submit'])) {
      $element['#validate'][] = [static::class, 'validateDefaultWorkspace'];

      // Ensure that the workspace validation is always shown, even when the
      // form element is limiting validation errors.
      if (isset($element['#limit_validation_errors']) && $element['#limit_validation_errors'] !== FALSE) {
        $element['#limit_validation_errors'][] = ['workspace_safe'];
      }
    }
  }

  /**
   * Validation handler which sets a validation error for all unsupported forms.
   */
  public static function validateDefaultWorkspace(array &$form, FormStateInterface $form_state): void {
    if ($form_state->get('workspace_safe') !== TRUE && isset($form_state->getCompleteForm()['workspace_safe'])) {
      $form_state->setErrorByName('workspace_safe', new TranslatableMarkup('This form can only be submitted in the default workspace.'));
    }
  }

}
