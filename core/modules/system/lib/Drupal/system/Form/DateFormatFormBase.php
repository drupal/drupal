<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatFormBase.
 */

namespace Drupal\system\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityFormController;

/**
 * Provides a base form controller for date formats.
 */
abstract class DateFormatFormBase extends EntityFormController implements EntityControllerInterface {

  /**
   * The date pattern type.
   *
   * @var string
   */
  protected $patternType;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateService;

  /**
   * Constructs a new date format form.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Datetime\Date $date_service
   *   The date service.
   */
  function __construct(ModuleHandlerInterface $module_handler, QueryFactory $query_factory, Date $date_service) {
    parent::__construct($module_handler);

    $date = new DrupalDateTime();
    $this->patternType = $date->canUseIntl() ? DrupalDateTime::INTL : DrupalDateTime::PHP;

    $this->queryFactory = $query_factory;
    $this->dateService = $date_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity.query'),
      $container->get('date')
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
    return (bool) $this->queryFactory
      ->get($this->entity->entityType())
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
      '#title' => t('Machine-readable name'),
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
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

    $languages = language_list();

    $options = array();
    foreach ($languages as $langcode => $data) {
      $options[$langcode] = $data->name;
    }

    if (!empty($options)) {
      $form['locales'] = array(
        '#title' => t('Select localizations'),
        '#type' => 'select',
        '#options' => $options,
        '#multiple' => TRUE,
        '#default_value' => $this->entity->getLocales(),
      );
    }

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
    $format = trim($form_state['values']['date_format_pattern']);
    $formats = $this->queryFactory
      ->get($this->entity->entityType())
      ->condition('pattern.' . $this->patternType, $format)
      ->execute();

    // Exclude the current format.
    unset($formats[$this->entity->id()]);
    if (!empty($formats)) {
      form_set_error('date_format_pattern', t('This format already exists. Enter a unique format string.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/config/regional/date-time';
    $form_state['values']['pattern'][$this->patternType] = trim($form_state['values']['date_format_pattern']);

    parent::submit($form, $form_state);
    $this->entity->save();
  }

}
