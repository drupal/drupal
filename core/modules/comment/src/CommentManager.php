<?php

namespace Drupal\comment;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Comment manager contains common functions to manage comment fields.
 */
class CommentManager implements CommentManagerInterface {
  use StringTranslationTrait;

  /**
   * Whether the \Drupal\user\RoleInterface::AUTHENTICATED_ID can post comments.
   *
   * @var bool
   */
  protected $authenticatedCanPostComments;

  /**
   * The user settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $userConfig;

  /**
   * Construct the CommentManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
    $this->userConfig = $config_factory->get('user.settings');
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return [];
    }

    $map = $this->entityFieldManager->getFieldMapByFieldType('comment');
    return $map[$entity_type_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function addBodyField($comment_type_id) {
    if (!FieldConfig::loadByName('comment', $comment_type_id, 'comment_body')) {
      // Attaches the body field by default.
      $field = $this->entityTypeManager->getStorage('field_config')->create([
        'label' => 'Comment',
        'bundle' => $comment_type_id,
        'required' => TRUE,
        'field_storage' => FieldStorageConfig::loadByName('comment', 'comment_body'),
      ]);
      $field->save();

      // Assign widget settings for the default form mode.
      $this->entityDisplayRepository->getFormDisplay('comment', $comment_type_id)
        ->setComponent('comment_body', [
          'type' => 'text_textarea',
        ])
        ->save();

      // Assign display settings for the default view mode.
      $this->entityDisplayRepository->getViewDisplay('comment', $comment_type_id)
        ->setComponent('comment_body', [
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ])
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function forbiddenMessage(EntityInterface $entity, $field_name) {
    if (!isset($this->authenticatedCanPostComments)) {
      // We only output a link if we are certain that users will get the
      // permission to post comments by logging in.
      $this->authenticatedCanPostComments = $this->entityTypeManager
        ->getStorage('user_role')
        ->load(RoleInterface::AUTHENTICATED_ID)
        ->hasPermission('post comments');
    }

    if ($this->authenticatedCanPostComments) {
      // We cannot use the redirect.destination service here because these links
      // sometimes appear on /node and taxonomy listing pages.
      if ($entity->get($field_name)->getFieldDefinition()->getSetting('form_location') == FormLocation::SeparatePage->value) {
        $comment_reply_parameters = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity' => $entity->id(),
          'field_name' => $field_name,
        ];
        $destination = ['destination' => Url::fromRoute('comment.reply', $comment_reply_parameters, ['fragment' => 'comment-form'])->toString()];
      }
      else {
        $destination = ['destination' => $entity->toUrl('canonical', ['fragment' => 'comment-form'])->toString()];
      }

      if ($this->userConfig->get('register') != UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
        // Users can register themselves.
        return $this->t('<a href=":login">Log in</a> or <a href=":register">register</a> to post comments', [
          ':login' => Url::fromRoute('user.login', [], ['query' => $destination])->toString(),
          ':register' => Url::fromRoute('user.register', [], ['query' => $destination])->toString(),
        ]);
      }
      else {
        // Only admins can add new users, no public registration.
        return $this->t('<a href=":login">Log in</a> to post comments', [
          ':login' => Url::fromRoute('user.login', [], ['query' => $destination])->toString(),
        ]);
      }
    }
    return '';
  }

}
