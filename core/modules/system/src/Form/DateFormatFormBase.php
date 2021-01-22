<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityForm;

/**
 * Provides a base form for date formats.
 */
abstract class DateFormatFormBase extends EntityForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct(DateFormatterInterface $date_formatter, ConfigEntityStorageInterface $date_format_storage) {
    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format')
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
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Name',
      '#maxlength' => 100,
      '#description' => $this->t('Name of the date format'),
      '#default_value' => $this->entity->label(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => $this->t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'),
      ],
    ];
    $form['date_format_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format string'),
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters">PHP manual</a> for available options.'),
      '#required' => TRUE,
      '#attributes' => [
        'data-drupal-date-formatter' => 'source',
      ],
      '#field_suffix' => ' <small class="js-hide" data-drupal-date-formatter="preview">' . $this->t('Displayed as %date_format', ['%date_format' => '']) . '</small>',
    ];

    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $this->entity->language()->getId(),
    ];
    $form['#attached']['drupalSettings']['dateFormats'] = $this->dateFormatter->getSampleDateFormats();
    $form['#attached']['library'][] = 'system/drupal.system.date';
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // The machine name field should already check to see if the requested
    // machine name is available.
    $pattern = trim($form_state->getValue('date_format_pattern'));
    foreach ($this->dateFormatStorage->loadMultiple() as $format) {
      if ($format->getPattern() == $pattern && ($format->id() == $this->entity->id())) {
        $this->messenger()->addStatus($this->t('The existing format/name combination has not been altered.'));
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
      $this->messenger()->addStatus($this->t('Custom date format updated.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Custom date format added.'));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
