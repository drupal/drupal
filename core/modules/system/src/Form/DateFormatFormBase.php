<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatFormBase.
 */

namespace Drupal\system\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityForm;

/**
 * Provides a base form for date formats.
 */
abstract class DateFormatFormBase extends EntityForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new date format form.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct(DateFormatter $date_formatter, ConfigEntityStorageInterface $date_format_storage) {
    $date = new DrupalDateTime();

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
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
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    return (bool) $this->dateFormatStorage
      ->getQuery()
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
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
        'error' => $this->t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'),
      ),
    );
    $form['date_format_pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Format string'),
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://php.net/manual/function.date.php')),
      '#required' => TRUE,
      '#attributes' => [
        'data-drupal-date-formatter' => 'source',
      ],
      '#field_suffix' => ' <small class="js-hide" data-drupal-date-formatter="preview">' . $this->t('Displayed as %date_format', ['%date_format' => '']) . '</small>',
    );

    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $this->entity->language()->getId(),
    );
    $form['#attached']['drupalSettings']['dateFormats'] = $this->dateFormatter->getSampleDateFormats();
    $form['#attached']['library'][] = 'system/drupal.system.date';
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // The machine name field should already check to see if the requested
    // machine name is available. Regardless of machine_name or human readable
    // name, check to see if the provided pattern exists.
    $pattern = trim($form_state->getValue('date_format_pattern'));
    foreach ($this->dateFormatStorage->loadMultiple() as $format) {
      if ($format->getPattern() == $pattern && ($this->entity->isNew() || $format->id() != $this->entity->id())) {
        $form_state->setErrorByName('date_format_pattern', $this->t('This format already exists. Enter a unique format string.'));
        continue;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('pattern', trim($form_state->getValue('date_format_pattern')));
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Custom date format updated.'));
    }
    else {
      drupal_set_message(t('Custom date format added.'));
    }
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

}
