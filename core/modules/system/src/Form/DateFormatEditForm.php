<?php

namespace Drupal\system\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a date format.
 *
 * @internal
 */
class DateFormatEditForm extends DateFormatFormBase {

  /**
   * Constructs a DateFormatEditForm object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $date_format_storage
   *   The date format storage.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(DateFormatterInterface $date_formatter, ConfigEntityStorageInterface $date_format_storage, protected ?TimeInterface $time = NULL) {
    parent::__construct($date_formatter, $date_format_storage);
    if ($this->time === NULL) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3112298', E_USER_DEPRECATED);
      $this->time = \Drupal::service('datetime.time');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $now = t('Displayed as %date', ['%date' => $this->dateFormatter->format($this->time->getRequestTime(), $this->entity->id())]);
    $form['date_format_pattern']['#field_suffix'] = ' <small data-drupal-date-formatter="preview">' . $now . '</small>';
    $form['date_format_pattern']['#default_value'] = $this->entity->getPattern();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save format');
    unset($actions['delete']);
    return $actions;
  }

}
