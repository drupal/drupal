<?php

declare(strict_types=1);

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\workspaces\WorkspaceInformationInterface;

/**
 * Provides a trait that marks Layout Builder forms as workspace-safe.
 */
trait WorkspaceSafeFormTrait {

  /**
   * The workspace information service.
   */
  protected ?WorkspaceInformationInterface $workspaceInfo = NULL;

  /**
   * Determines whether the current form is safe to be submitted in a workspace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if the form is workspace-safe, FALSE otherwise.
   */
  public function isWorkspaceSafeForm(array $form, FormStateInterface $form_state): bool {
    $section_storage = $this->sectionStorage ?: $this->getSectionStorageFromFormState($form_state);
    if ($section_storage) {
      $context_definitions = $section_storage->getContextDefinitions();
      if (!empty($context_definitions['entity'])) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $section_storage->getContext('entity')->getContextValue();
        $supported = $entity && $this->getWorkspaceInfo()->isEntitySupported($entity);
        $ignored = $entity && $this->getWorkspaceInfo()->isEntityIgnored($entity);

        if ($supported || $ignored) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Retrieves the section storage from a form state object, if it exists.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage or NULL if it doesn't exist.
   */
  protected function getSectionStorageFromFormState(FormStateInterface $form_state): ?SectionStorageInterface {
    foreach ($form_state->getBuildInfo()['args'] as $argument) {
      if ($argument instanceof SectionStorageInterface) {
        return $argument;
      }
    }

    return NULL;
  }

  /**
   * Retrieves the workspace information service.
   *
   * @return \Drupal\workspaces\WorkspaceInformationInterface
   *   The workspace information service.
   */
  protected function getWorkspaceInfo(): WorkspaceInformationInterface {
    if (!$this->workspaceInfo) {
      $this->workspaceInfo = \Drupal::service('workspaces.information');
    }

    return $this->workspaceInfo;
  }

}
