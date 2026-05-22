<?php

declare(strict_types=1);

namespace Drupal\field_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for enabling/disabling entity view displays.
 *
 * Redirects back to the overview page after toggling status.
 */
class EntityViewDisplayStatusController extends ControllerBase {

  public function __construct(
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Enables or disables an entity view display.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $view_mode_name
   *   The view mode name.
   * @param string $operation
   *   The operation: 'enable' or 'disable'.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the overview page.
   */
  public function toggleStatus(string $entity_type_id, string $view_mode_name, string $operation, RouteMatchInterface $route_match): RedirectResponse {
    // Get bundle from route match.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_entity_type = $entity_type->getBundleEntityType();
    if ($bundle_entity_type) {
      $bundle_entity = $route_match->getParameter($bundle_entity_type);
      $bundle = $bundle_entity ? $bundle_entity->id() : $route_match->getRawParameter($bundle_entity_type);
    }
    else {
      $bundle = $route_match->getRawParameter('bundle') ?: $entity_type_id;
    }

    $view_modes = $this->entityDisplayRepository->getViewModes($entity_type_id);
    if ($view_mode_name !== 'default' && !isset($view_modes[$view_mode_name])) {
      throw new \InvalidArgumentException(sprintf("Invalid view mode '%s' for entity type '%s'.", $view_mode_name, $entity_type_id));
    }

    // Prevent disabling the default view mode.
    if ($view_mode_name === 'default' && $operation === 'disable') {
      throw new \InvalidArgumentException('The default view mode cannot be disabled.');
    }

    $storage = $this->entityTypeManager->getStorage('entity_view_display');
    $default_display_id = $entity_type_id . '.' . $bundle . '.default';
    $requested_display_id = $entity_type_id . '.' . $bundle . '.' . $view_mode_name;

    $ids_to_load = [$default_display_id];
    if ($view_mode_name !== 'default') {
      $ids_to_load[] = $requested_display_id;
    }
    $displays = $storage->loadMultiple($ids_to_load);

    if (isset($displays[$requested_display_id])) {
      $display = $displays[$requested_display_id];
    }
    elseif ($view_mode_name !== 'default' && isset($displays[$default_display_id])) {
      $display = $displays[$default_display_id]->createCopy($view_mode_name);
    }
    else {
      $display = $storage->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => $view_mode_name,
      ]);
    }

    $display->setStatus($operation === 'enable');
    $display->save();

    $view_mode_label = $view_modes[$view_mode_name]['label'] ?? $view_mode_name;
    $message = $operation === 'enable'
      ? $this->t('The %view_mode view mode has been enabled.', ['%view_mode' => $view_mode_label])
      : $this->t('The %view_mode view mode has been disabled.', ['%view_mode' => $view_mode_label]);

    $this->messenger()->addStatus($message);
    $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $bundle);
    $overview_url = Url::fromRoute("entity.entity_view_display_overview.{$entity_type_id}", $route_parameters);
    return new RedirectResponse($overview_url->toString());
  }

}
