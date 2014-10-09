<?php

/**
 * @file
 * Contains \Drupal\comment\Form\CommentAdminOverview.
 */

namespace Drupal\comment\Form;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentStorageInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the comments overview administration form.
 */
class CommentAdminOverview extends FormBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a CommentAdminOverview form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\comment\CommentStorageInterface $comment_storage
   *   The comment storage.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, CommentStorageInterface $comment_storage, DateFormatter $date_formatter, ModuleHandlerInterface $module_handler) {
    $this->entityManager = $entity_manager;
    $this->commentStorage = $comment_storage;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.manager')->getStorage('comment'),
      $container->get('date.formatter'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'comment_admin_overview';
  }

  /**
   * Form constructor for the comment overview administration form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $type
   *   The type of the overview form ('approval' or 'new').
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'new') {

    // Build an 'Update options' form.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );

    if ($type == 'approval') {
      $options['publish'] = $this->t('Publish the selected comments');
    }
    else {
      $options['unpublish'] = $this->t('Unpublish the selected comments');
    }
    $options['delete'] = $this->t('Delete the selected comments');

    $form['options']['operation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    );
    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    );

    // Load the comments that need to be displayed.
    $status = ($type == 'approval') ? CommentInterface::NOT_PUBLISHED : CommentInterface::PUBLISHED;
    $header = array(
      'subject' => array(
        'data' => $this->t('Subject'),
        'specifier' => 'subject',
      ),
      'author' => array(
        'data' => $this->t('Author'),
        'specifier' => 'name',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'posted_in' => array(
        'data' => $this->t('Posted in'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'changed' => array(
        'data' => $this->t('Updated'),
        'specifier' => 'changed',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'operations' => $this->t('Operations'),
    );
    $cids = $this->commentStorage->getQuery()
     ->condition('status', $status)
     ->tableSort($header)
     ->pager(50)
     ->execute();

    /** @var $comments \Drupal\comment\CommentInterface[] */
    $comments = $this->commentStorage->loadMultiple($cids);

    // Build a table listing the appropriate comments.
    $options = array();
    $destination = drupal_get_destination();

    $commented_entity_ids = array();
    $commented_entities = array();

    foreach ($comments as $comment) {
      $commented_entity_ids[$comment->getCommentedEntityTypeId()][] = $comment->getCommentedEntityId();
    }

    foreach ($commented_entity_ids as $entity_type => $ids) {
      $commented_entities[$entity_type] = $this->entityManager->getStorage($entity_type)->loadMultiple($ids);
    }

    foreach ($comments as $comment) {
      /** @var $commented_entity \Drupal\Core\Entity\EntityInterface */
      $commented_entity = $commented_entities[$comment->getCommentedEntityTypeId()][$comment->getCommentedEntityId()];
      $username = array(
        '#theme' => 'username',
        '#account' => comment_prepare_author($comment),
      );
      $body = '';
      if (!empty($comment->comment_body->value)) {
        $body = $comment->comment_body->value;
      }
      $comment_permalink = $comment->permalink();
      $attributes = $comment_permalink->getOption('attributes') ?: array();
      $attributes += array('title' => Unicode::truncate($body, 128));
      $comment_permalink->setOption('attributes', $attributes);
      $options[$comment->id()] = array(
        'title' => array('data' => array('#title' => $comment->getSubject() ?: $comment->id())),
        'subject' => array(
          'data' => array(
            '#type' => 'link',
            '#title' => $comment->getSubject(),
            '#url' => $comment_permalink,
          ),
        ),
        'author' => drupal_render($username),
        'posted_in' => array(
          'data' => array(
            '#type' => 'link',
            '#title' => $commented_entity->label(),
            '#access' => $commented_entity->access('view'),
            '#url' => $commented_entity->urlInfo(),
          ),
        ),
        'changed' => $this->dateFormatter->format($comment->getChangedTime(), 'short'),
      );
      $comment_uri_options = $comment->urlInfo()->getOptions();
      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.comment.edit_form', ['comment' => $comment->id()], $comment_uri_options + ['query' => $destination]),
      );
      if ($this->moduleHandler->moduleExists('content_translation') && $this->moduleHandler->invoke('content_translation', 'translate_access', array($comment))->isAllowed()) {
        $links['translate'] = array(
          'title' => $this->t('Translate'),
          'url' => Url::fromRoute('content_translation.translation_overview_comment', ['comment' => $comment->id()], $comment_uri_options + ['query' => $destination]),
        );
      }
      $options[$comment->id()]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }

    $form['comments'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No comments available.'),
    );

    $form['pager'] = array('#theme' => 'pager');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('comments', array_diff($form_state->getValue('comments'), array(0)));
    // We can't execute any 'Update options' if no comments were selected.
    if (count($form_state->getValue('comments')) == 0) {
      $form_state->setErrorByName('', $this->t('Select one or more comments to perform the update on.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $cids = $form_state->getValue('comments');

    foreach ($cids as $cid) {
      // Delete operation handled in \Drupal\comment\Form\ConfirmDeleteMultiple
      // see \Drupal\comment\Controller\AdminController::adminPage().
      if ($operation == 'unpublish') {
        $comment = $this->commentStorage->load($cid);
        $comment->setPublished(FALSE);
        $comment->save();
      }
      elseif ($operation == 'publish') {
        $comment = $this->commentStorage->load($cid);
        $comment->setPublished(TRUE);
        $comment->save();
      }
    }
    drupal_set_message($this->t('The update has been performed.'));
    $form_state->setRedirect('comment.admin');
  }

}
