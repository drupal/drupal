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

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  public function __construct(?ThemeHandlerInterface $theme_handler = NULL) {
    if (!$theme_handler instanceof ThemeHandlerInterface) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $theme_handler argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3015925', E_USER_DEPRECATED);
      $theme_handler = \Drupal::service(ThemeHandlerInterface::class);
    }
    $this->themeHandler = $theme_handler;
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

  /**
   * Wraps system_region_list().
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Use
   *   $this->themeHandler->getTheme()->listAllRegions() or
   *   $this->themeHandler->getTheme()->listVisibleRegions() instead.
   *
   * @see https://www.drupal.org/node/3015925
   */
  // @phpstan-ignore-next-line
  protected function systemRegionList($theme, $show = REGIONS_ALL) {
    @trigger_error(__CLASS__ . '::systemRegionList() is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Use $this->themeHandler->getTheme()->listAllRegions() or $this->themeHandler->getTheme()->listVisibleRegions() instead. See https://www.drupal.org/node/3015925', E_USER_DEPRECATED);
    return $show === 'all' ? $this->themeHandler->getTheme($theme)->listAllRegions() : $this->themeHandler->getTheme($theme)->listVisibleRegions();
  }

}
