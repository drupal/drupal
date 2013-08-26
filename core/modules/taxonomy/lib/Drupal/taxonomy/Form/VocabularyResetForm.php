<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Form\VocabularyReset.
 */

namespace Drupal\taxonomy\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class VocabularyResetForm extends EntityConfirmFormBase {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new VocabularyResetForm object.
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
    return 'taxonomy_vocabulary_confirm_reset_alphabetical';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to reset the vocabulary %title to alphabetical order?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/taxonomy/manage/' . $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Resetting a vocabulary will discard all custom ordering and sort items alphabetically.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reset to alphabetical');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->connection->update('taxonomy_term_data')
      ->fields(array('weight' => 0))
      ->condition('vid', $this->entity->id())
      ->execute();

    drupal_set_message(t('Reset vocabulary %name to alphabetical order.', array('%name' => $this->entity->label())));
    watchdog('taxonomy', 'Reset vocabulary %name to alphabetical order.', array('%name' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/taxonomy/manage/' . $this->entity->id();
  }

}
