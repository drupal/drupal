<?php

namespace Drupal\comment;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form handler for comment type edit forms.
 *
 * @internal
 */
class CommentTypeForm extends EntityForm {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The comment manager.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('comment'),
      $container->get('comment.manager')
    );
  }

  /**
   * Constructs a CommentTypeFormController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, CommentManagerInterface $comment_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->commentManager = $comment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $comment_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $comment_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $comment_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\comment\Entity\CommentType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$comment_type->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $comment_type->getDescription(),
      '#description' => t('Describe this comment type. The text will be displayed on the <em>Comment types</em> administration overview page.'),
      '#title' => t('Description'),
    ];

    if ($comment_type->isNew()) {
      $options = [];
      // Only expose entities that have field UI enabled, only those can
      // get comment fields added in the UI. Also, ensure to include only
      // entities that have integer id.
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
        if ($this->entityTypeSupportsComments($entity_type)) {
          if ($entity_type->get('field_ui_base_route')) {
            $options[$entity_type->id()] = $entity_type->getLabel();
          }
        }
      }
      $form['target_entity_type_id'] = [
        '#type' => 'select',
        '#default_value' => $comment_type->getTargetEntityTypeId(),
        '#title' => t('Target entity type'),
        '#options' => $options,
        '#description' => t('The target entity type can not be changed after the comment type has been created.'),
      ];
    }
    else {
      $form['target_entity_type_id_display'] = [
        '#type' => 'item',
        '#markup' => $this->entityTypeManager->getDefinition($comment_type->getTargetEntityTypeId())->getLabel(),
        '#title' => t('Target entity type'),
      ];
    }

    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('comment', $comment_type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'comment',
          'bundle' => $comment_type->id(),
        ],
        '#default_value' => $language_configuration,
      ];

      $form['#submit'][] = 'language_configuration_element_submit';
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * Wraps _comment_entity_uses_integer_id().
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type being tested.
   *
   * @return bool
   *   TRUE if entity-type uses integer IDs.
   */
  protected function entityTypeSupportsComments(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements(FieldableEntityInterface::class) && _comment_entity_uses_integer_id($entity_type->id());
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $comment_type = $this->entity;
    $status = $comment_type->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus(t('Comment type %label has been updated.', ['%label' => $comment_type->label()]));
      $this->logger->notice('Comment type %label has been updated.', ['%label' => $comment_type->label(), 'link' => $edit_link]);
    }
    else {
      $this->commentManager->addBodyField($comment_type->id());
      $this->messenger()->addStatus(t('Comment type %label has been added.', ['%label' => $comment_type->label()]));
      $this->logger->notice('Comment type %label has been added.', ['%label' => $comment_type->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirectUrl($comment_type->toUrl('collection'));
  }

}
