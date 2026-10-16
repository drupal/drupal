<?php

namespace Drupal\comment\Form;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Entity\Form\BundleEntityDeleteForm;
use Drupal\field\Entity\FieldStorageConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting a comment type entity.
 *
 * @internal
 */
class CommentTypeDeleteForm extends BundleEntityDeleteForm {

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

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
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(CommentManagerInterface $comment_manager, LoggerInterface $logger) {
    $this->commentManager = $comment_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('comment.manager'),
      $container->get('logger.factory')->get('comment')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalReasonsToPreventDeletion(): array {
    $reasons = parent::getAdditionalReasonsToPreventDeletion();

    $entity_type = $this->entity->getTargetEntityTypeId();
    foreach (array_keys($this->commentManager->getFields($entity_type)) as $field_name) {
      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
      if (($field_storage = FieldStorageConfig::loadByName($entity_type, $field_name)) && $field_storage->getSetting('comment_type') == $this->entity->id() && !$field_storage->isDeleted()) {
        $reasons[] = $this->t('%label is used by the %field field on your site. You may not remove this comment type until you have removed the field.', [
          '%label' => $this->entity->label(),
          '%field' => $field_storage->label(),
        ]);
      }
    }

    return $reasons;
  }

}
