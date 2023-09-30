<?php

namespace Drupal\node;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for node type forms.
 *
 * @internal
 */
class NodeTypeForm extends BundleEntityFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add content type');
      $fields = $this->entityFieldManager->getBaseFieldDefinitions('node');
      // Create a node with a fake bundle using the type's UUID so that we can
      // get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      $node = $this->entityTypeManager->getStorage('node')->create(['type' => $type->uuid()]);
    }
    else {
      $form['#title'] = $this->t('Edit %label content type', ['%label' => $type->label()]);
      $fields = $this->entityFieldManager->getFieldDefinitions('node', $type->id());
      // Create a node to get the current values for workflow settings fields.
      $node = $this->entityTypeManager->getStorage('node')->create(['type' => $type->id()]);
    }

    $form['name'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name for this content type, displayed on the <em>Content types</em> page.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['type'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => [
        'exists' => ['Drupal\node\Entity\NodeType', 'load'],
        'source' => ['name'],
      ],
      '#description' => $this->t('Unique machine-readable name: lowercase letters, numbers, and underscores only.', [
        '%node-add' => $this->t('Add content'),
      ]),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('Displays on the <em>Content types</em> page.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['node/drupal.content_types'],
      ],
    ];

    $form['submission'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];
    $form['submission']['title_label'] = [
      '#title' => $this->t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    ];
    $form['submission']['preview_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Preview before submitting'),
      '#default_value' => $type->getPreviewMode(),
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
      ],
    ];
    $form['submission']['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => $this->t('This text will be displayed at the top of the page when creating or editing content of this type.'),
    ];
    $form['workflow'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing options'),
      '#group' => 'additional_settings',
    ];
    $workflow_options = [
      'status' => $node->status->value,
      'promote' => $node->promote->value,
      'sticky' => $node->sticky->value,
      'revision' => $type->shouldCreateNewRevision(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => [
        'status' => $this->t('Published'),
        'promote' => $this->t('Promoted to front page'),
        'sticky' => $this->t('Sticky at top of lists'),
        'revision' => $this->t('Create new revision'),
      ],
      '#description' => $this->t('Users with sufficient access rights will be able to override these options.'),
    ];
    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('node', $type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'node',
          'bundle' => $type->id(),
        ],
        '#default_value' => $language_configuration,
      ];
    }
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display settings'),
      '#group' => 'additional_settings',
    ];
    $form['display']['display_submitted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display author and date information'),
      '#default_value' => $type->displaySubmitted(),
      '#description' => $this->t('Author username and publish date will be displayed.'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $type->setNewRevision($form_state->getValue(['options', 'revision']));
    $type->set('type', trim($type->id()));
    $type->set('name', trim($type->label()));

    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The content type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      node_add_body_field($type);
      $this->messenger()->addStatus($this->t('The content type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $type->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('node')->notice('Added content type %name.', $context);
    }

    $fields = $this->entityFieldManager->getFieldDefinitions('node', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($type->id())->setLabel($title_label)->save();
    }
    // Update workflow options.
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    $node = $this->entityTypeManager->getStorage('node')->create(['type' => $type->id()]);
    foreach (['status', 'promote', 'sticky'] as $field_name) {
      $value = (bool) $form_state->getValue(['options', $field_name]);
      if ($node->$field_name->value != $value) {
        $fields[$field_name]->getConfig($type->id())->setDefaultValue($value)->save();
      }
    }

    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
