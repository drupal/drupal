<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatDeleteForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a form to delete a date format.
 */
class DateFormatDeleteForm extends EntityConfirmFormBase implements EntityControllerInterface {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * Constructs an DateFormatDeleteForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Date $date_service) {
    parent::__construct($module_handler);

    $this->dateService = $date_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('date')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to remove the format %name : %format?', array(
      '%name' => $this->entity->label(),
      '%format' => $this->dateService->format(REQUEST_TIME, $this->entity->id()))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/regional/date-time';
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('Removed date format %format.', array('%format' => $this->entity->label())));

    $form_state['redirect'] = 'admin/config/regional/date-time';
  }

}
