<?php

namespace Drupal\block\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 *
 * @internal
 */
class BlockDeleteForm extends EntityDeleteForm {

  public function __construct(protected ThemeHandlerInterface $themeHandler) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('theme_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('block.admin_display');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->getEntity();
    $regions = $this->themeHandler->getTheme($entity->getTheme())->listVisibleRegions();
    return $this->t('Are you sure you want to remove the @entity-type %label from the %region region?', [
      '@entity-type' => $entity->getEntityType()->getSingularLabel(),
      '%label' => $entity->label(),
      '%region' => $regions[$entity->getRegion()],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will remove the block placement. You will need to <a href=":url">place it again</a> in order to undo this action.', [
      ':url' => Url::fromRoute('block.admin_display_theme', ['theme' => $this->getEntity()->getTheme()])->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    $regions = $this->themeHandler->getTheme($entity->getTheme())->listVisibleRegions();
    return $this->t('The @entity-type %label has been removed from the %region region.', [
      '@entity-type' => $entity->getEntityType()->getSingularLabel(),
      '%label' => $entity->label(),
      '%region' => $regions[$entity->getRegion()],
    ]);
  }

}
