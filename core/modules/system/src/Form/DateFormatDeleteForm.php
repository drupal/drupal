<?php

namespace Drupal\system\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a form to delete a date format.
 *
 * @internal
 */
class DateFormatDeleteForm extends EntityDeleteForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a DateFormatDeleteForm object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(DateFormatterInterface $date_formatter, protected ?TimeInterface $time = NULL) {
    $this->dateFormatter = $date_formatter;
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
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the format %name : %format?', [
      '%name' => $this->entity->label(),
      '%format' => $this->dateFormatter->format($this->time->getRequestTime(), $this->entity->id()),
    ]);
  }

}
