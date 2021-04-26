<?php

namespace Drupal\comment\Form;

use Drupal\comment\CommentInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the comments overview administration form.
 *
 * @internal
 */
class CommentAdminOverview extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Creates a CommentAdminOverview form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler, PrivateTempStoreFactory $temp_store_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->commentStorage = $entity_type_manager->getStorage('comment');
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('module_handler'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
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
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];

    if ($type == 'approval') {
      $options['publish'] = $this->t('Publish the selected comments');
    }
    else {
      $options['unpublish'] = $this->t('Unpublish the selected comments');
    }
    $options['delete'] = $this->t('Delete the selected comments');

    $form['options']['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    ];
    $form['options']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    // Load the comments that need to be displayed.
    $status = ($type == 'approval') ? CommentInterface::NOT_PUBLISHED : CommentInterface::PUBLISHED;
    $header = [
      'subject' => [
        'data' => $this->t('Subject'),
        'specifier' => 'subject',
      ],
      'author' => [
        'data' => $this->t('Author'),
        'specifier' => 'name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'posted_in' => [
        'data' => $this->t('Posted in'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'changed' => [
        'data' => $this->t('Updated'),
        'specifier' => 'changed',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'operations' => $this->t('Operations'),
    ];
    $cids = $this->commentStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', $status)
      ->tableSort($header)
      ->pager(50)
      ->execute();

    /** @var $comments \Drupal\comment\CommentInterface[] */
    $comments = $this->commentStorage->loadMultiple($cids);

    // Build a table listing the appropriate comments.
    $options = [];
    $destination = $this->getDestinationArray();

    $commented_entity_ids = [];
    $commented_entities = [];

    foreach ($comments as $comment) {
      $commented_entity_ids[$comment->getCommentedEntityTypeId()][] = $comment->getCommentedEntityId();
    }

    foreach ($commented_entity_ids as $entity_type => $ids) {
      $commented_entities[$entity_type] = $this->entityTypeManager
        ->getStorage($entity_type)
        ->loadMultiple($ids);
    }

    foreach ($comments as $comment) {
      /** @var $commented_entity \Drupal\Core\Entity\EntityInterface */
      $commented_entity = $commented_entities[$comment->getCommentedEntityTypeId()][$comment->getCommentedEntityId()];
      $comment_permalink = $comment->permalink();
      if ($comment->hasField('comment_body') && ($body = $comment->get('comment_body')->value)) {
        $attributes = $comment_permalink->getOption('attributes') ?: [];
        $attributes += ['title' => Unicode::truncate($body, 128)];
        $comment_permalink->setOption('attributes', $attributes);
      }
      $options[$comment->id()] = [
        'title' => ['data' => ['#title' => $comment->getSubject() ?: $comment->id()]],
        'subject' => [
          'data' => [
            '#type' => 'link',
            '#title' => $comment->getSubject(),
            '#url' => $comment_permalink,
          ],
        ],
        'author' => [
          'data' => [
            '#theme' => 'username',
            '#account' => $comment->getOwner(),
          ],
        ],
        'posted_in' => [
          'data' => [
            '#type' => 'link',
            '#title' => $commented_entity->label(),
            '#access' => $commented_entity->access('view'),
            '#url' => $commented_entity->toUrl(),
          ],
        ],
        'changed' => $this->dateFormatter->format($comment->getChangedTimeAcrossTranslations(), 'short'),
      ];
      $comment_uri_options = $comment->toUrl()->getOptions() + ['query' => $destination];
      $links = [];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => $comment->toUrl('edit-form', $comment_uri_options),
      ];
      if ($this->moduleHandler->moduleExists('content_translation') && $this->moduleHandler->invoke('content_translation', 'translate_access', [$comment])->isAllowed()) {
        $links['translate'] = [
          'title' => $this->t('Translate'),
          'url' => $comment->toUrl('drupal:content-translation-overview', $comment_uri_options),
        ];
      }
      $options[$comment->id()]['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    $form['comments'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No comments available.'),
    ];

    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('comments', array_diff($form_state->getValue('comments'), [0]));
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
    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = $this->commentStorage->loadMultiple($cids);
    if ($operation != 'delete') {
      foreach ($comments as $comment) {
        if ($operation == 'unpublish') {
          $comment->setUnpublished();
        }
        elseif ($operation == 'publish') {
          $comment->setPublished();
        }
        $comment->save();
      }
      $this->messenger()->addStatus($this->t('The update has been performed.'));
      $form_state->setRedirect('comment.admin');
    }
    else {
      $info = [];
      /** @var \Drupal\comment\CommentInterface $comment */
      foreach ($comments as $comment) {
        $langcode = $comment->language()->getId();
        $info[$comment->id()][$langcode] = $langcode;
      }
      $this->tempStoreFactory
        ->get('entity_delete_multiple_confirm')
        ->set($this->currentUser()->id() . ':comment', $info);
      $form_state->setRedirect('entity.comment.delete_multiple_form');
    }
  }

}
