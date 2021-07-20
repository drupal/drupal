<?php

namespace Drupal\comment\Plugin\Field\FieldType;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Entity\CommentType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'comment' field type.
 *
 * @FieldType(
 *   id = "comment",
 *   label = @Translation("Comments"),
 *   description = @Translation("This field manages configuration and presentation of comments on an entity."),
 *   list_class = "\Drupal\comment\CommentFieldItemList",
 *   default_widget = "comment_default",
 *   default_formatter = "comment_default",
 *   cardinality = 1,
 * )
 */
class CommentItem extends FieldItemBase implements CommentItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'comment_type' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'default_mode' => CommentManagerInterface::COMMENT_MODE_THREADED,
      'thread_limit' => [
        'depth' => 2,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_ALLOW,
      ],
      'per_page' => 50,
      'form_location' => CommentItemInterface::FORM_BELOW,
      'anonymous' => CommentInterface::ANONYMOUS_MAYNOT_CONTACT,
      'preview' => DRUPAL_OPTIONAL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Comment status'))
      ->setRequired(TRUE);

    $properties['cid'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment ID'));

    $properties['last_comment_timestamp'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment timestamp'))
      ->setDescription(t('The time that the last comment was created.'));

    $properties['last_comment_name'] = DataDefinition::create('string')
      ->setLabel(t('Last comment name'))
      ->setDescription(t('The name of the user posting the last comment.'));

    $properties['last_comment_uid'] = DataDefinition::create('integer')
      ->setLabel(t('Last comment user ID'));

    $properties['comment_count'] = DataDefinition::create('integer')
      ->setLabel(t('Number of comments'))
      ->setDescription(t('The number of comments.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'status' => [
          'description' => 'Whether comments are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $settings = $this->getSettings();

    // Check access for anonymous user.
    $anonymous_user = new AnonymousUserSession();
    $anonymous_user_access = \Drupal::entityTypeManager()
      ->getAccessControlHandler('comment')
      ->createAccess($this->getSetting('comment_type'), $anonymous_user);

    $element['default_mode'] = [
      '#type' => 'radios',
      '#title' => t('Threading mode'),
      '#options' => [
        CommentManagerInterface::COMMENT_MODE_FLAT => $this->t('Flat list'),
        CommentManagerInterface::COMMENT_MODE_THREADED => $this->t('Threaded (no depth limit)'),
        CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT => $this->t('Threaded (limited depth)'),
      ],
      '#default_value' => $settings['default_mode'],
      '#description' => $this->t('If the comments are displayed as a flat list or threaded.'),
    ];
    $element['thread_limit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Limited depth settings'),
      '#states' => [
        'visible' => [
          ':input[name="settings[default_mode]"]' => [
            'value' => CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT,
          ],
        ],
      ],
    ];
    $element['thread_limit']['depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Thread depth limit'),
      '#default_value' => $settings['thread_limit']['depth'],
      '#description' => $this->t('Define comment thread depth.'),
      // The maximum thread deep cannot be less than 2 as it doesn't makes sense
      // to have threaded comments with only one level.
      '#min' => 2,
    ];
    $element['thread_limit']['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Replying to deepest comment'),
      '#default_value' => $settings['thread_limit']['mode'],
      '#options' => [
        CommentItemInterface::THREAD_DEPTH_REPLY_MODE_ALLOW => $this->t('Allowed'),
        CommentItemInterface::THREAD_DEPTH_REPLY_MODE_DENY => $this->t('Denied'),
      ],
      CommentItemInterface::THREAD_DEPTH_REPLY_MODE_ALLOW => [
        '#description' => $this->t('Users are able to reply to the deepest comment. The reply will be displayed on the same level as the comment that receives the reply.'),
      ],
      CommentItemInterface::THREAD_DEPTH_REPLY_MODE_DENY => [
        '#description' => $this->t('Users cannot reply to the deepest comment.'),
      ],
    ];
    $element['per_page'] = [
      '#type' => 'number',
      '#title' => t('Comments per page'),
      '#default_value' => $settings['per_page'],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 1000,
    ];
    $element['anonymous'] = [
      '#type' => 'select',
      '#title' => t('Anonymous commenting'),
      '#default_value' => $settings['anonymous'],
      '#options' => [
        CommentInterface::ANONYMOUS_MAYNOT_CONTACT => t('Anonymous posters may not enter their contact information'),
        CommentInterface::ANONYMOUS_MAY_CONTACT => t('Anonymous posters may leave their contact information'),
        CommentInterface::ANONYMOUS_MUST_CONTACT => t('Anonymous posters must leave their contact information'),
      ],
      '#access' => $anonymous_user_access,
    ];
    $element['form_location'] = [
      '#type' => 'checkbox',
      '#title' => t('Show reply form on the same page as comments'),
      '#default_value' => $settings['form_location'],
    ];
    $element['preview'] = [
      '#type' => 'radios',
      '#title' => t('Preview comment'),
      '#default_value' => $settings['preview'],
      '#options' => [
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // There is always a value for this field, it is one of
    // CommentItemInterface::OPEN, CommentItemInterface::CLOSED or
    // CommentItemInterface::HIDDEN.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    // @todo Inject entity storage once typed-data supports container injection.
    //   See https://www.drupal.org/node/2053415 for more details.
    $comment_types = CommentType::loadMultiple();
    $options = [];
    $entity_type = $this->getEntity()->getEntityTypeId();
    foreach ($comment_types as $comment_type) {
      if ($comment_type->getTargetEntityTypeId() == $entity_type) {
        $options[$comment_type->id()] = $comment_type->label();
      }
    }
    $element['comment_type'] = [
      '#type' => 'select',
      '#title' => t('Comment type'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Select the Comment type to use for this comment field. Manage the comment types from the <a href=":url">administration overview page</a>.', [':url' => Url::fromRoute('entity.comment_type.collection')->toString()]),
      '#default_value' => $this->getSetting('comment_type'),
      '#disabled' => $has_data,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $statuses = [
      CommentItemInterface::HIDDEN,
      CommentItemInterface::CLOSED,
      CommentItemInterface::OPEN,
    ];
    return [
      'status' => $statuses[mt_rand(0, count($statuses) - 1)],
    ];
  }

}
