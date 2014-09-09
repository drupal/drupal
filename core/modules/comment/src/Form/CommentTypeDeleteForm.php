<?php

/**
 * @file
 * Contains \Drupal\comment\Form\CommentTypeDeleteForm.
 */

namespace Drupal\comment\Form;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting a comment type entity.
 */
class CommentTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\comment\CommentTypeInterface
   */
  protected $entity;

  /**
   * Constructs a query factory object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(QueryFactory $query_factory, CommentManagerInterface $comment_manager, EntityManager $entity_manager, LoggerInterface $logger) {
    $this->queryFactory = $query_factory;
    $this->commentManager = $comment_manager;
    $this->entityManager = $entity_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('comment.manager'),
      $container->get('entity.manager'),
      $container->get('logger.factory')->get('comment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('comment.type_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $comments = $this->queryFactory->get('comment')->condition('comment_type', $this->entity->id())->execute();
    $entity_type = $this->entity->getTargetEntityTypeId();
    $caption = '';
    foreach (array_keys($this->commentManager->getFields($entity_type)) as $field_name) {
      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
      if (($field_storage = FieldStorageConfig::loadByName($entity_type, $field_name)) && $field_storage->getSetting('comment_type') == $this->entity->id() && !$field_storage->deleted) {
        $caption .= '<p>' . $this->t('%label is used by the %field field on your site. You can not remove this comment type until you have removed the field.', array(
          '%label' => $this->entity->label(),
          '%field' => $field_storage->label(),
        )) . '</p>';
      }
    }

    if (!empty($comments)) {
      $caption .= '<p>' . $this->formatPlural(count($comments), '%label is used by 1 comment on your site. You can not remove this comment type until you have removed all of the %label comments.', '%label is used by @count comments on your site. You may not remove %label until you have removed all of the %label comments.', array('%label' => $this->entity->label())) . '</p>';
    }
    if ($caption) {
      $form['description'] = array('#markup' => $caption);
      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $form_state->setRedirect('comment.type_list');
    drupal_set_message($this->t('Comment type %label has been deleted.', array('%label' => $this->entity->label())));
    $this->logger->notice('comment type %label has been deleted.', array('%label' => $this->entity->label()));
  }

}
