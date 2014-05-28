<?php

/**
 * @file
 * Contains \Drupal\dblog\Form\DblogClearLogForm.
 */

namespace Drupal\dblog\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the form that clears out the log.
 */
class DblogClearLogForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new DblogClearLogForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'dblog_clear_log_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['dblog_clear'] = array(
      '#type' => 'details',
      '#title' => $this->t('Clear log messages'),
      '#description' => $this->t('This will permanently remove the log messages from the database.'),
    );
    $form['dblog_clear']['clear'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clear log messages'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $_SESSION['dblog_overview_filter'] = array();
    $this->connection->delete('watchdog')->execute();
    drupal_set_message($this->t('Database log cleared.'));
  }

}
