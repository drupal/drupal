<?php

/**
 * @file
 * Contains \Drupal\comment\Form\ConfirmDeleteMultiple.
 */

namespace Drupal\comment\Form;

use Drupal\comment\CommentStorageInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the comment multiple delete confirmation form.
 */
class ConfirmDeleteMultiple extends ConfirmFormBase {

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * An array of comments to be deleted.
   *
   * @var \Drupal\comment\CommentInterface[]
   */
  protected $comments;

  /**
   * Creates an new ConfirmDeleteMultiple form.
   *
   * @param \Drupal\comment\CommentStorageInterface $comment_storage
   *   The comment storage.
   */
  public function __construct(CommentStorageInterface $comment_storage) {
    $this->commentStorage = $comment_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('comment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete these comments and all their children?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('comment.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete comments');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $edit = $form_state->getUserInput();

    $form['comments'] = array(
      '#prefix' => '<ul>',
      '#suffix' => '</ul>',
      '#tree' => TRUE,
    );
    // array_filter() returns only elements with actual values.
    $comment_counter = 0;
    $this->comments = $this->commentStorage->loadMultiple(array_keys(array_filter($edit['comments'])));
    foreach ($this->comments as $comment) {
      $cid = $comment->id();
      $form['comments'][$cid] = array(
        '#type' => 'hidden',
        '#value' => $cid,
        '#prefix' => '<li>',
        '#suffix' => String::checkPlain($comment->label()) . '</li>'
      );
      $comment_counter++;
    }
    $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');

    if (!$comment_counter) {
      drupal_set_message($this->t('There do not appear to be any comments to delete, or your selected comment was deleted by another administrator.'));
      $form_state->setRedirect('comment.admin');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      $this->commentStorage->delete($this->comments);
      $count = count($form_state->getValue('comments'));
      $this->logger('content')->notice('Deleted @count comments.', array('@count' => $count));
      drupal_set_message($this->formatPlural($count, 'Deleted 1 comment.', 'Deleted @count comments.'));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
