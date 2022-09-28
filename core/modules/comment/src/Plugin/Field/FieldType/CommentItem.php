<?php

namespace Drupal\comment\Plugin\Field\FieldType;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Entity\CommentType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
      ->setLabel(new TranslatableMarkup('Comment status'))
      ->setRequired(TRUE);

    $properties['cid'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Last comment ID'));

    $properties['last_comment_timestamp'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Last comment timestamp'))
      ->setDescription(new TranslatableMarkup('The time that the last comment was created.'));

    $properties['last_comment_name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Last comment name'))
      ->setDescription(new TranslatableMarkup('The name of the user posting the last comment.'));

    $properties['last_comment_uid'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Last comment user ID'));

    $properties['comment_count'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Number of comments'))
      ->setDescription(new TranslatableMarkup('The number of comments.'));

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

    $anonymous_user = new AnonymousUserSession();

    $element['default_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Threading'),
      '#default_value' => $settings['default_mode'],
      '#description' => $this->t('Show comment replies in a threaded list.'),
    ];
    $element['per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Comments per page'),
      '#default_value' => $settings['per_page'],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 1000,
    ];
    $element['anonymous'] = [
      '#type' => 'select',
      '#title' => $this->t('Anonymous commenting'),
      '#default_value' => $settings['anonymous'],
      '#options' => [
        CommentInterface::ANONYMOUS_MAYNOT_CONTACT => $this->t('Anonymous posters may not enter their contact information'),
        CommentInterface::ANONYMOUS_MAY_CONTACT => $this->t('Anonymous posters may leave their contact information'),
        CommentInterface::ANONYMOUS_MUST_CONTACT => $this->t('Anonymous posters must leave their contact information'),
      ],
      '#access' => $anonymous_user->hasPermission('post comments'),
    ];
    $element['form_location'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reply form on the same page as comments'),
      '#default_value' => $settings['form_location'],
    ];
    $element['preview'] = [
      '#type' => 'radios',
      '#title' => $this->t('Preview comment'),
      '#default_value' => $settings['preview'],
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
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
      '#title' => $this->t('Comment type'),
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
