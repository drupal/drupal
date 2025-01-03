<?php

declare(strict_types=1);

namespace Drupal\database_test\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for database_test module.
 *
 * @internal
 */
class DatabaseTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'database_test_theme_tablesort';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = [
      'username' => ['data' => $this->t('Username'), 'field' => 'u.name'],
      'status' => ['data' => $this->t('Status'), 'field' => 'u.status'],
    ];

    $query = Database::getConnection()->select('users_field_data', 'u');
    $query->condition('u.uid', 0, '<>');
    $query->condition('u.default_langcode', 1);

    $count_query = clone $query;
    $count_query->addExpression('COUNT([u].[uid])');

    $query = $query
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query
      ->fields('u', ['uid'])
      ->limit(50)
      ->orderByHeader($header)
      ->setCountQuery($count_query);
    $uids = $query
      ->execute()
      ->fetchCol();

    $options = [];

    foreach (User::loadMultiple($uids) as $account) {
      $options[$account->id()] = [
        'title' => ['data' => ['#title' => $account->getAccountName()]],
        'username' => $account->getAccountName(),
        'status' => $account->isActive() ? $this->t('active') : $this->t('blocked'),
      ];
    }

    $form['accounts'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No people available.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
