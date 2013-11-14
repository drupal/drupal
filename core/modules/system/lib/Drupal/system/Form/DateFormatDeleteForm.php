<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatDeleteForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Datetime\Date;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a form to delete a date format.
 */
class DateFormatDeleteForm extends EntityConfirmFormBase {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * Constructs an DateFormatDeleteForm object.
   *
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   */
  public function __construct(Date $date_service) {
    $this->dateService = $date_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
  public function getCancelRoute() {
    return array(
      'route_name' => 'system.date_format_list',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('Removed date format %format.', array('%format' => $this->entity->label())));

    $form_state['redirect_route']['route_name'] = 'system.date_format_list';
  }

}
