<?php

namespace Drupal\system\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a form to delete a date format.
 */
class DateFormatDeleteForm extends EntityDeleteForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs an DateFormatDeleteForm object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the format %name : %format?', [
      '%name' => $this->entity->label(),
      '%format' => $this->dateFormatter->format(REQUEST_TIME, $this->entity->id()),
    ]);
  }

}
