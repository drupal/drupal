<?php

/**
 * @file
 * Contains \Drupal\comment\Form\DeleteForm.
 */

namespace Drupal\comment\Form;

use Drupal\comment\CommentManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends EntityNGConfirmFormBase {

  /**
   * The comment manager.
   *
   * @var \Drupal\comment\CommentManager
   */
  protected $commentManager;

  /**
   * Constructs a DeleteForm object.
   *
   * @param \Drupal\comment\CommentManager $comment_manager
   *   The comment manager service.
   */
  public function __construct(CommentManager $comment_manager) {
    $this->commentManager = $comment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('comment.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the comment %title?', array('%title' => $this->entity->subject->value));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1987778.
    $uri = $this->commentManager->getParentEntityUri($this->entity);
    $actions['cancel']['#href'] = $uri['path'];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any replies to this comment will be lost. This action cannot be undone.');
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
  public function submit(array $form, array &$form_state) {
    // Delete the comment and its replies.
    $this->entity->delete();
    drupal_set_message($this->t('The comment and all its replies have been deleted.'));
    watchdog('content', 'Deleted comment @cid and its replies.', array('@cid' => $this->entity->id()));
    // Clear the cache so an anonymous user sees that his comment was deleted.
    Cache::invalidateTags(array('content' => TRUE));

    $uri = $this->commentManager->getParentEntityUri($this->entity);
    $form_state['redirect'] = $uri['path'];
  }

}
