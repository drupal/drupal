<?php

/**
 * @file
 * Contains \Drupal\comment\CommentManager.
 */

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\field\FieldInfo;

/**
 * Comment manager contains common functions to manage comment fields.
 */
class CommentManager implements CommentManagerInterface {

  /**
   * The field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface $current_user
   */
  protected $currentUser;

  /**
   * Whether the DRUPAL_AUTHENTICATED_RID can post comments.
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
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Construct the CommentManager object.
   *
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translation service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   */
  public function __construct(FieldInfo $field_info, EntityManagerInterface $entity_manager, AccountInterface $current_user, ConfigFactoryInterface $config_factory, TranslationInterface $translation_manager, UrlGeneratorInterface $url_generator) {
    $this->fieldInfo = $field_info;
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->userConfig = $config_factory->get('user.settings');
    $this->translationManager = $translation_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntityUri(CommentInterface $comment) {
    return $this->entityManager
      ->getStorageController($comment->getCommentedEntityTypeId())
      ->load($comment->getCommentedEntityId())
      ->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_type_id) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    if (!$entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      return array();
    }

    $map = $this->getAllFields();
    return isset($map[$entity_type_id]) ? $map[$entity_type_id] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFields() {
    $map = $this->fieldInfo->getFieldMap();
    // Build a list of comment fields only.
    $comment_fields = array();
    foreach ($map as $entity_type => $data) {
      foreach ($data as $field_name => $field_info) {
        if ($field_info['type'] == 'comment') {
          $comment_fields[$entity_type][$field_name] = $field_info;
        }
      }
    }
    return $comment_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function addDefaultField($entity_type, $bundle, $field_name = 'comment', $default_value = CommentItemInterface::OPEN) {
    // Make sure the field doesn't already exist.
    if (!$this->fieldInfo->getField($entity_type, $field_name)) {
      // Add a default comment field for existing node comments.
      $field = $this->entityManager->getStorageController('field_config')->create(array(
        'entity_type' => $entity_type,
        'name' => $field_name,
        'type' => 'comment',
        'translatable' => '0',
        'settings' => array(
          'description' => 'Default comment field',
        ),
      ));
      // Create the field.
      $field->save();
    }
    // Make sure the instance doesn't already exist.
    if (!$this->fieldInfo->getInstance($entity_type, $bundle, $field_name)) {
      $instance = $this->entityManager->getStorageController('field_instance_config')->create(array(
        'label' => 'Comment settings',
        'description' => '',
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'required' => 1,
        'default_value' => array(
          array(
            'status' => $default_value,
            'cid' => 0,
            'last_comment_name' => '',
            'last_comment_timestamp' => 0,
            'last_comment_uid' => 0,
          ),
        ),
      ));
      $instance->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'comment_default',
          'weight' => 20,
        ))
        ->save();

      // Set default to display comment list.
      entity_get_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'label' => 'hidden',
          'type' => 'comment_default',
          'weight' => 20,
        ))
        ->save();
    }
    $this->addBodyField($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function addBodyField($entity_type, $field_name) {
    // Create the field if needed.
    $field = $this->entityManager->getStorageController('field_config')->load('comment.comment_body');
    if (!$field) {
      $field = $this->entityManager->getStorageController('field_config')->create(array(
        'name' => 'comment_body',
        'type' => 'text_long',
        'entity_type' => 'comment',
      ));
      $field->save();
    }
    // Create the instance if needed, field name defaults to 'comment'.
    $comment_bundle = $entity_type . '__' . $field_name;
    $field_instance = $this->entityManager
      ->getStorageController('field_instance_config')
      ->load("comment.$comment_bundle.comment_body");
    if (!$field_instance) {
      // Attaches the body field by default.
      $field_instance = $this->entityManager->getStorageController('field_instance_config')->create(array(
        'field_name' => 'comment_body',
        'label' => 'Comment',
        'entity_type' => 'comment',
        'bundle' => $comment_bundle,
        'settings' => array('text_processing' => 1),
        'required' => TRUE,
      ));
      $field_instance->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('comment', $comment_bundle, 'default')
        ->setComponent('comment_body', array(
          'type' => 'text_textarea',
        ))
        ->save();

      // Assign display settings for the 'default' view mode.
      entity_get_display('comment', $comment_bundle, 'default')
        ->setComponent('comment_body', array(
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ))
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldUIPageTitle($commented_entity_type, $field_name) {
    $field_info = $this->fieldInfo->getField($commented_entity_type, $field_name);
    $bundles = $field_info->getBundles();
    $sample_bundle = reset($bundles);
    $sample_instance = $this->fieldInfo->getInstance($commented_entity_type, $sample_bundle, $field_name);
    return String::checkPlain($sample_instance->label);
  }

  /**
   * {@inheritdoc}
   */
  public function forbiddenMessage(EntityInterface $entity, $field_name) {
    if ($this->currentUser->isAnonymous()) {
      if (!isset($this->authenticatedCanPostComments)) {
        // We only output a link if we are certain that users will get the
        // permission to post comments by logging in.
        $this->authenticatedCanPostComments = $this->entityManager
          ->getStorageController('user_role')
          ->load(DRUPAL_AUTHENTICATED_RID)
          ->hasPermission('post comments');
      }

      if ($this->authenticatedCanPostComments) {
        // We cannot use drupal_get_destination() because these links
        // sometimes appear on /node and taxonomy listing pages.
        if ($entity->get($field_name)->getFieldDefinition()->getSetting('form_location') == COMMENT_FORM_SEPARATE_PAGE) {
          $destination = array('destination' => 'comment/reply/' . $entity->getEntityTypeId() . '/' . $entity->id() . '/' . $field_name . '#comment-form');
        }
        else {
          $destination = array('destination' => $entity->getSystemPath() . '#comment-form');
        }

        if ($this->userConfig->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
          // Users can register themselves.
          return $this->t('<a href="@login">Log in</a> or <a href="@register">register</a> to post comments', array(
            '@login' => $this->urlGenerator->generateFromRoute('user.login', array(), array('query' => $destination)),
            '@register' => $this->urlGenerator->generateFromRoute('user.register', array(), array('query' => $destination)),
          ));
        }
        else {
          // Only admins can add new users, no public registration.
          return $this->t('<a href="@login">Log in</a> to post comments', array(
            '@login' => $this->urlGenerator->generateFromRoute('user.login', array(), array('query' => $destination)),
          ));
        }
      }
    }
    return '';
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

}
