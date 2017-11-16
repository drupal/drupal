<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides AJAX responses to rebuild the Layout Builder.
 *
 * @internal
 */
trait LayoutRebuildTrait {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Rebuilds the layout.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  protected function rebuildAndClose(EntityInterface $entity) {
    $response = $this->rebuildLayout($entity);
    $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
    return $response;
  }

  /**
   * Rebuilds the layout.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to either rebuild the layout and close the dialog, or
   *   reload the page.
   */
  protected function rebuildLayout(EntityInterface $entity) {
    $response = new AjaxResponse();
    $layout_controller = $this->classResolver->getInstanceFromDefinition(LayoutBuilderController::class);
    $layout = $layout_controller->layout($entity, TRUE);
    $response->addCommand(new ReplaceCommand('#layout-builder', $layout));
    return $response;
  }

}
