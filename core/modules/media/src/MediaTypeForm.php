<?php

namespace Drupal\media;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\media\Entity\MediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for media type forms.
 *
 * @internal
 */
class MediaTypeForm extends EntityForm {

  /**
   * Media source plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $sourceManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $source_manager
   *   Media source plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   */
  public function __construct(PluginManagerInterface $source_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->sourceManager = $source_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.media.source'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Ajax callback triggered by the type provider select element.
   */
  public function ajaxHandlerData(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#source-dependent', $form['source_dependent']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Source is not set when the entity is initially created.
    /** @var \Drupal\media\MediaSourceInterface $source */
    $source = $this->entity->get('source') ? $this->entity->getSource() : NULL;

    if ($this->operation === 'add') {
      $form['#title'] = $this->t('Add media type');
    }

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The human-readable name of this media type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength' => 32,
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'exists' => [MediaType::class, 'load'],
      ],
      '#description' => $this->t('A unique machine-readable name for this media type.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('Describe this media type. The text will be displayed on the <em>Add new media</em> page.'),
    ];

    $plugins = $this->sourceManager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }

    $form['source_dependent'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'source-dependent'],
    ];

    if (!$this->entity->isNew()) {
      $source_description = $this->t('<em>The media source cannot be changed after the media type is created.</em>');
    }
    else {
      $source_description = $this->t('Media source that is responsible for additional logic related to this media type.');
    }
    $form['source_dependent']['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Media source'),
      '#default_value' => $source ? $source->getPluginId() : NULL,
      '#options' => $options,
      '#description' => $source_description,
      '#ajax' => ['callback' => '::ajaxHandlerData'],
      '#required' => TRUE,
      // Once the media type is created, its source plugin cannot be changed
      // anymore.
      '#disabled' => !$this->entity->isNew(),
    ];

    if ($source) {
      // Media source plugin configuration.
      $form['source_dependent']['source_configuration'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Media source configuration'),
        '#tree' => TRUE,
      ];

      $form['source_dependent']['source_configuration'] = $source->buildConfigurationForm($form['source_dependent']['source_configuration'], $this->getSourceSubFormState($form, $form_state));
    }

    // Field mapping configuration.
    $form['source_dependent']['field_map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Field mapping'),
      '#tree' => TRUE,
      'description' => [
        '#markup' => '<p>' . $this->t('Media sources can provide metadata fields such as title, caption, size information, credits, etc. Media can automatically save this metadata information to entity fields, which can be configured below. Information will only be mapped if the entity field is empty.') . '</p>',
      ],
    ];

    if (empty($source) || empty($source->getMetadataAttributes())) {
      $form['source_dependent']['field_map']['#access'] = FALSE;
    }
    else {
      $options = [MediaSourceInterface::METADATA_FIELD_EMPTY => $this->t('- Skip field -')];
      foreach ($this->entityFieldManager->getFieldDefinitions('media', $this->entity->id()) as $field_name => $field) {
        if (!($field instanceof BaseFieldDefinition) || $field_name === 'name') {
          $options[$field_name] = $field->getLabel();
        }
      }

      $field_map = $this->entity->getFieldMap();
      foreach ($source->getMetadataAttributes() as $metadata_attribute_name => $metadata_attribute_label) {
        $form['source_dependent']['field_map'][$metadata_attribute_name] = [
          '#type' => 'select',
          '#title' => $metadata_attribute_label,
          '#options' => $options,
          '#default_value' => isset($field_map[$metadata_attribute_name]) ? $field_map[$metadata_attribute_name] : MediaSourceInterface::METADATA_FIELD_EMPTY,
        ];
      }
    }

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['media/type_form'],
      ],
    ];

    $form['workflow'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing options'),
      '#group' => 'additional_settings',
    ];

    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default options'),
      '#default_value' => $this->getWorkflowOptions(),
      '#options' => [
        'status' => $this->t('Published'),
        'new_revision' => $this->t('Create new revision'),
        'queue_thumbnail_downloads' => $this->t('Queue thumbnail downloads'),
      ],
    ];

    $form['workflow']['options']['status']['#description'] = $this->t('Media will be automatically published when created.');
    $form['workflow']['options']['new_revision']['#description'] = $this->t('Automatically create new revisions. Users with the "Administer media" permission will be able to override this option.');
    $form['workflow']['options']['queue_thumbnail_downloads']['#description'] = $this->t('Download thumbnails via a queue. When using remote media sources, the thumbnail generation could be a slow process. Using a queue allows for this process to be handled in the background.');

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('media', $this->entity->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'media',
          'bundle' => $this->entity->id(),
        ],
        '#default_value' => $language_configuration,
      ];
    }

    return $form;
  }

  /**
   * Prepares workflow options to be used in the 'checkboxes' form element.
   *
   * @return array
   *   Array of options ready to be used in #options.
   */
  protected function getWorkflowOptions() {
    $workflow_options = [
      'status' => $this->entity->getStatus(),
      'new_revision' => $this->entity->shouldCreateNewRevision(),
      'queue_thumbnail_downloads' => $this->entity->thumbnailDownloadsAreQueued(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    return array_combine($keys, $keys);
  }

  /**
   * Gets subform state for the media source configuration subform.
   *
   * @param array $form
   *   Full form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Parent form state.
   *
   * @return \Drupal\Core\Form\SubformStateInterface
   *   Sub-form state for the media source configuration form.
   */
  protected function getSourceSubFormState(array $form, FormStateInterface $form_state) {
    return SubformState::createForSubform($form['source_dependent']['source_configuration'], $form, $form_state)
      ->set('operation', $this->operation)
      ->set('type', $this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (isset($form['source_dependent']['source_configuration'])) {
      // Let the selected plugin validate its settings.
      $this->entity->getSource()->validateConfigurationForm($form['source_dependent']['source_configuration'], $this->getSourceSubFormState($form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('field_map', array_filter(
      $form_state->getValue('field_map', []),
      function ($item) {
        return $item != MediaSourceInterface::METADATA_FIELD_EMPTY;
      }
    ));

    parent::submitForm($form, $form_state);

    $this->entity->setQueueThumbnailDownloadsStatus((bool) $form_state->getValue(['options', 'queue_thumbnail_downloads']))
      ->setStatus((bool) $form_state->getValue(['options', 'status']))
      ->setNewRevision((bool) $form_state->getValue(['options', 'new_revision']));

    if (isset($form['source_dependent']['source_configuration'])) {
      // Let the selected plugin save its settings.
      $this->entity->getSource()->submitConfigurationForm($form['source_dependent']['source_configuration'], $this->getSourceSubFormState($form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // If the media source has not been chosen yet, turn the submit button into
    // a button. This rebuilds the form with the media source's configuration
    // form visible, instead of saving the media type. This allows users to
    // create a media type without JavaScript enabled. With JavaScript enabled,
    // this rebuild occurs during an AJAX request.
    // @see \Drupal\media\MediaTypeForm::ajaxHandlerData()
    if (empty($this->getEntity()->get('source'))) {
      $actions['submit']['#type'] = 'button';
    }

    $actions['submit']['#value'] = $this->t('Save');
    $actions['delete']['#value'] = $this->t('Delete');
    $actions['delete']['#access'] = $this->entity->access('delete');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = $this->entity;

    // If the media source is using a source field, ensure it's
    // properly created.
    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);
    if (!$source_field) {
      $source_field = $source->createSourceField($media_type);
      /** @var \Drupal\field\FieldStorageConfigInterface $storage */
      $storage = $source_field->getFieldStorageDefinition();
      if ($storage->isNew()) {
        $storage->save();
      }
      $source_field->save();

      // Add the new field to the default form and view displays for this
      // media type.
      if ($source_field->isDisplayConfigurable('form')) {
        // @todo Replace entity_get_form_display() when #2367933 is done.
        // https://www.drupal.org/node/2872159.
        $display = entity_get_form_display('media', $media_type->id(), 'default');
        $source->prepareFormDisplay($media_type, $display);
        $display->save();
      }
      if ($source_field->isDisplayConfigurable('view')) {
        // @todo Replace entity_get_display() when #2367933 is done.
        // https://www.drupal.org/node/2872159.
        $display = entity_get_display('media', $media_type->id(), 'default');
        $source->prepareViewDisplay($media_type, $display);
        $display->save();
      }
    }

    $t_args = ['%name' => $media_type->label()];
    if ($status === SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The media type %name has been updated.', $t_args));
    }
    elseif ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The media type %name has been added.', $t_args));
      $this->logger('media')->notice('Added media type %name.', $t_args);
    }

    // Override the "status" base field default value, for this media type.
    $fields = $this->entityFieldManager->getFieldDefinitions('media', $media_type->id());
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->entityTypeManager->getStorage('media')->create(['bundle' => $media_type->id()]);
    $value = (bool) $form_state->getValue(['options', 'status']);
    if ($media->status->value != $value) {
      $fields['status']->getConfig($media_type->id())->setDefaultValue($value)->save();
    }

    $form_state->setRedirectUrl($media_type->toUrl('collection'));
  }

}
