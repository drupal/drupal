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
  public function getFormId() {
    return 'taxonomy_vocabulary_confirm_reset_alphabetical';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the vocabulary %title to alphabetical order?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'taxonomy.overview_terms',
      'route_parameters' => array(
        'taxonomy_vocabulary' => $this->entity->id(),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Resetting a vocabulary will discard all custom ordering and sort items alphabetically.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset to alphabetical');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->connection->update('taxonomy_term_data')
      ->fields(array('weight' => 0))
      ->condition('vid', $this->entity->id())
      ->execute();

    drupal_set_message($this->t('Reset vocabulary %name to alphabetical order.', array('%name' => $this->entity->label())));
    watchdog('taxonomy', 'Reset vocabulary %name to alphabetical order.', array('%name' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/taxonomy/manage/' . $this->entity->id();
  }

}
