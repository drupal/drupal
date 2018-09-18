<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Form\ViewsExposedForm;
use Drupal\workspaces\Form\WorkspaceFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to form operations.
 *
 * @internal
 */
class FormOperations implements ContainerInjectionInterface {

  /**
   * The workspace manager service.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new FormOperations instance.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.manager')
    );
  }

  /**
   * Alters forms to disallow editing in non-default workspaces.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    // No alterations are needed in the default workspace.
    if ($this->workspaceManager->getActiveWorkspace()->isDefaultWorkspace()) {
      return;
    }

    // Add an additional validation step for every form if we are in a
    // non-default workspace.
    $this->addWorkspaceValidation($form);

    // If a form has already been marked as safe or not to submit in a
    // non-default workspace, we don't have anything else to do.
    if ($form_state->has('workspace_safe')) {
      return;
    }

    // No forms are safe to submit in a non-default workspace by default, except
    // for the whitelisted ones defined below.
    $workspace_safe = FALSE;

    // Whitelist a few forms that we know are safe to submit.
    $form_object = $form_state->getFormObject();
    $is_workspace_form = $form_object instanceof WorkspaceFormInterface;
    $is_search_form = in_array($form_object->getFormId(), ['search_block_form', 'search_form'], TRUE);
    $is_views_exposed_form = $form_object instanceof ViewsExposedForm;
    if ($is_workspace_form || $is_search_form || $is_views_exposed_form) {
      $workspace_safe = TRUE;
    }

    $form_state->set('workspace_safe', $workspace_safe);
  }

  /**
   * Adds our validation handler recursively on each element of a form.
   *
   * @param array &$element
   *   An associative array containing the structure of the form.
   */
  protected function addWorkspaceValidation(array &$element) {
    // Recurse through all children and add our validation handler if needed.
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]) && $element[$key]) {
        $this->addWorkspaceValidation($element[$key]);
      }
    }

    if (isset($element['#validate'])) {
      $element['#validate'][] = [get_called_class(), 'validateDefaultWorkspace'];
    }
  }

  /**
   * Validation handler which sets a validation error for all unsupported forms.
   */
  public static function validateDefaultWorkspace(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('workspace_safe') !== TRUE) {
      $form_state->setError($form, new TranslatableMarkup('This form can only be submitted in the default workspace.'));
    }
  }

}
