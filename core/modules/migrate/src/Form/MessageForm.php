<?php

namespace Drupal\migrate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate messages form.
 *
 * @internal
 */
class MessageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static();
    $form->setStringTranslation($container->get('string_translation'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_messages_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $session_filters = $this->getRequest()->getSession()->get('migration_messages_overview_filter', []);
    $form['filters'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Filter messages'),
      '#weight' => 0,
    ];
    $form['filters']['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $session_filters['message']['value'] ?? '',
    ];
    $form['filters']['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity level'),
      '#default_value' => $session_filters['severity']['value'] ?? [],
      '#options' => [
        MigrationInterface::MESSAGE_ERROR => $this->t('Error'),
        MigrationInterface::MESSAGE_WARNING => $this->t('Warning'),
        MigrationInterface::MESSAGE_NOTICE => $this->t('Notice'),
        MigrationInterface::MESSAGE_INFORMATIONAL => $this->t('Info'),
      ],
      '#multiple' => TRUE,
      '#size' => 4,
    ];
    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    $form['filters']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $filters['message'] = [
      'title' => $this->t('message'),
      'where' => 'msg.message LIKE ?',
      'type' => 'string',
    ];
    $filters['severity'] = [
      'title' => $this->t('Severity'),
      'where' => 'msg.level = ?',
      'type' => 'array',
    ];
    $session_filters = $this->getRequest()->getSession()->get('migration_messages_overview_filter', []);
    foreach ($filters as $name => $filter) {
      if ($form_state->hasValue($name)) {
        $session_filters[$name] = [
          'where' => $filter['where'],
          'value' => $form_state->getValue($name),
          'type' => $filter['type'],
        ];
      }
    }
    $this->getRequest()->getSession()->set('migration_messages_overview_filter', $session_filters);
  }

  /**
   * Resets the filter form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array $form, FormStateInterface $form_state): void {
    $this->getRequest()->getSession()->remove('migration_messages_overview_filter');
  }

}
