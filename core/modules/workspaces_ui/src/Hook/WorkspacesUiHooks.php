<?php

declare(strict_types=1);

namespace Drupal\workspaces_ui\Hook;

use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\workspaces_ui\Form\WorkspaceActivateForm;
use Drupal\workspaces_ui\Form\WorkspaceDeleteForm;
use Drupal\workspaces_ui\Form\WorkspaceForm;
use Drupal\workspaces_ui\WorkspaceListBuilder;
use Drupal\workspaces_ui\WorkspaceViewBuilder;

/**
 * Hook implementations for the workspaces_ui module.
 */
class WorkspacesUiHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (isset($entity_types['workspace'])) {
      $entity_types['workspace']->setHandlerClass('list_builder', WorkspaceListBuilder::class);
      $entity_types['workspace']->setHandlerClass('view_builder', WorkspaceViewBuilder::class);
      $entity_types['workspace']->setHandlerClass('route_provider', [
        'html' => AdminHtmlRouteProvider::class,
      ]);
      $entity_types['workspace']->setFormClass('default', WorkspaceForm::class);
      $entity_types['workspace']->setFormClass('add', WorkspaceForm::class);
      $entity_types['workspace']->setFormClass('edit', WorkspaceForm::class);
      $entity_types['workspace']->setFormClass('delete', WorkspaceDeleteForm::class);
      $entity_types['workspace']->setFormClass('activate', WorkspaceActivateForm::class);
      $entity_types['workspace']->setLinkTemplate('canonical', '/admin/config/workflow/workspaces/manage/{workspace}');
      $entity_types['workspace']->setLinkTemplate('add-form', '/admin/config/workflow/workspaces/add');
      $entity_types['workspace']->setLinkTemplate('edit-form', '/admin/config/workflow/workspaces/manage/{workspace}/edit');
      $entity_types['workspace']->setLinkTemplate('delete-form', '/admin/config/workflow/workspaces/manage/{workspace}/delete');
      $entity_types['workspace']->setLinkTemplate('activate-form', '/admin/config/workflow/workspaces/manage/{workspace}/activate');
      $entity_types['workspace']->setLinkTemplate('collection', '/admin/config/workflow/workspaces');
      $entity_types['workspace']->set('field_ui_base_route', 'entity.workspace.collection');
    }
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      // Main module help for the Workspaces UI module.
      case 'help.page.workspaces_ui':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Workspaces UI module provides an interface for managing workspaces for the <a href=":workspaces_module">Workspaces module</a>. For more information, see the <a href=":workspaces">online documentation for the Workspaces UI module</a>.', [
          ':workspaces_module' => Url::fromRoute('help.page', ['name' => 'workspaces'])
            ->toString(),
          ':workspaces' => 'https://www.drupal.org/docs/8/core/modules/workspace/overview',
        ]) . '</p>';
        return $output;
    }
    return NULL;
  }

}
