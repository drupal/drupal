<?php

declare(strict_types=1);

namespace Drupal\workspaces\Controller;

use Drupal\Core\Controller\FormController;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\workspaces\Plugin\Validation\Constraint\EntityWorkspaceConflictConstraint;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides the entity form controller service for workspaces operations.
 */
class WorkspacesHtmlEntityFormController extends FormController {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  public function __construct(
    protected readonly FormController $entityFormController,
    protected readonly WorkspaceManagerInterface $workspaceManager,
    protected readonly WorkspaceInformationInterface $workspaceInfo,
    protected readonly TypedDataManagerInterface $typedDataManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getContentResult(Request $request, RouteMatchInterface $route_match): array {
    $form_arg = $this->getFormArgument($route_match);
    $form_object = $this->getFormObject($route_match, $form_arg);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_object->getEntity();
    if ($this->workspaceInfo->isEntitySupported($entity)) {
      $active_workspace = $this->workspaceManager->getActiveWorkspace();

      // Prepare a minimal render array in case we need to return it.
      $build['#cache']['contexts'] = $entity->getCacheContexts();
      $build['#cache']['tags'] = $entity->getCacheTags();
      $build['#cache']['max-age'] = $entity->getCacheMaxAge();

      // Prevent entities from being edited if they're tracked in workspace.
      if ($form_object->getOperation() !== 'delete') {
        $constraints = array_values(array_filter($entity->getTypedData()->getConstraints(), function ($constraint) {
          return $constraint instanceof EntityWorkspaceConflictConstraint;
        }));

        if (!empty($constraints)) {
          $violations = $this->typedDataManager->getValidator()->validate(
            $entity->getTypedData(),
            $constraints[0]
          );
          if (count($violations)) {
            $build['#markup'] = $violations->get(0)->getMessage();

            return $build;
          }
        }
      }

      // Prevent entities from being deleted in a workspace if they have a
      // published default revision.
      if ($form_object->getOperation() === 'delete' && $active_workspace && !$this->workspaceInfo->isEntityDeletable($entity, $active_workspace)) {
        $build['#markup'] = $this->t('This @entity_type_label can only be deleted in the Live workspace.', [
          '@entity_type_label' => $entity->getEntityType()->getSingularLabel(),
        ]);

        return $build;
      }
    }

    return $this->entityFormController->getContentResult($request, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match): string {
    return $this->entityFormController->getFormArgument($route_match);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormObject(RouteMatchInterface $route_match, $form_arg): FormInterface {
    return $this->entityFormController->getFormObject($route_match, $form_arg);
  }

}
