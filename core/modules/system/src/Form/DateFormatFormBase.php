<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatFormBase.
 */

namespace Drupal\system\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityForm;

/**
 * Provides a base form for date formats.
 */
abstract class DateFormatFormBase extends EntityForm {

  /**
   * The date pattern type.
   *
   * @var string
   */
  protected $patternType;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new date format form.
   *
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct(Date $date_service, ConfigEntityStorageInterface $date_format_storage) {
    $date = new DrupalDateTime();
    $this->patternType = $date->canUseIntl() ? DrupalDateTime::INTL : DrupalDateTime::PHP;

    $this->dateService = $date_service;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * Checks for an existing date format.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param array $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element,  array $form_state) {
    return (bool) $this->dateFormatStorage
      ->getQuery()
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * Returns the date for a given format string.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX Response to update the date-time value of the date format.
   */
  public static function dateTimeLookup(array $form, array $form_state) {
    $format = '';
    if (!empty($form_state['values']['date_format_pattern'])) {
      $format = t('Displayed as %date_format', array('%date_format' => \Drupal::service('date')->format(REQUEST_TIME, 'custom', $form_state['values']['date_format_pattern'])));
    }
    // Return a command instead of a string, since the Ajax framework
    // automatically prepends an additional empty DIV element for a string, which
    // breaks the layout.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#edit-date-format-suffix', '<small id="edit-date-format-suffix">' . $format . '</small>'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => 'Name',
      '#maxlength' => 100,
      '#description' => t('Name of the date format'),
      '#default_value' => $this->entity->label(),
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' =>'([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
    );

    if (class_exists('intlDateFormatter')) {
      $description = t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://userguide.icu-project.org/formatparse/datetime'));
    }
    else {
      $description = t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://php.net/manual/function.date.php'));
    }
    $form['date_format_pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Format string'),
      '#maxlength' => 100,
      '#description' => $description,
      '#default_value' => '',
      '#field_suffix' => ' <small id="edit-date-format-suffix"></small>',
      '#ajax' => array(
        'callback' => array($this, 'dateTimeLookup'),
        'event' => 'keyup',
        'progress' => array('type' => 'throbber', 'message' => NULL),
      ),
      '#required' => TRUE,
    );

    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Language'),
      '#languages' => Language::STATE_ALL,
      '#default_value' => $this->entity->langcode,
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    // The machine name field should already check to see if the requested
    // machine name is available. Regardless of machine_name or human readable
    // name, check to see if the provided pattern exists.
    $pattern = trim($form_state['values']['date_format_pattern']);
    foreach ($this->dateFormatStorage->loadMultiple() as $format) {
      if ($format->getPattern() == $pattern && ($this->entity->isNew() || $format->id() != $this->entity->id())) {
        $this->setFormError('date_format_pattern', $form_state, $this->t('This format already exists. Enter a unique format string.'));
        continue;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $form_state['redirect_route']['route_name'] = 'system.date_format_list';
    $form_state['values']['pattern'][$this->patternType] = trim($form_state['values']['date_format_pattern']);

    parent::submit($form, $form_state);
    $this->entity->save();
  }

}
