<?php

namespace Drupal\Core\Entity;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity form variant for content entity types.
 *
 * @see \Drupal\Core\ContentEntityBase
 */
class ContentEntityForm extends EntityForm implements ContentEntityFormInterface {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\RevisionLogInterface
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    // Hide the current revision log message in UI.
    if ($this->showRevisionUi() && !$this->entity->isNew() && $this->entity instanceof RevisionLogInterface) {
      $this->entity->setRevisionLogMessage(NULL);
    }
  }

  /**
   * Returns the bundle entity of the entity, or NULL if there is none.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The bundle entity.
   */
  protected function getBundleEntity() {
    if ($bundle_entity_type = $this->entity->getEntityType()->getBundleEntityType()) {
      return $this->entityTypeManager->getStorage($bundle_entity_type)->load($this->entity->bundle());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    if ($this->showRevisionUi()) {
      // Advanced tab must be the first, because other fields rely on that.
      if (!isset($form['advanced'])) {
        $form['advanced'] = [
          '#type' => 'vertical_tabs',
          '#weight' => 99,
        ];
      }
    }

    $form = parent::form($form, $form_state);

    // Content entity forms do not use the parent's #after_build callback
    // because they only need to rebuild the entity in the validation and the
    // submit handler because Field API uses its own #after_build callback for
    // its widgets.
    unset($form['#after_build']);

    $this->getFormDisplay($form_state)->buildForm($this->entity, $form, $form_state);
    // Allow modules to act before and after form language is updated.
    $form['#entity_builders']['update_form_langcode'] = '::updateFormLangcode';

    if ($this->showRevisionUi()) {
      $this->addRevisionableFormFields($form);
    }

    $form['footer'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => ['entity-content-form-footer'],
      ],
      '#optional' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Update the changed timestamp of the entity.
    $this->updateChangedTime($this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Mark the entity as requiring validation.
    $entity->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));

    // Save as a new revision if requested to do so.
    if ($this->showRevisionUi() && !$form_state->isValueEmpty('revision')) {
      $entity->setNewRevision();
      if ($entity instanceof RevisionLogInterface) {
        // If a new revision is created, save the current user as
        // revision author.
        $entity->setRevisionUserId($this->currentUser()->id());
        $entity->setRevisionCreationTime($this->time->getRequestTime());
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   *
   * Button-level validation handlers are highly discouraged for entity forms,
   * as they will prevent entity validation from running. If the entity is going
   * to be saved during the form submission, this method should be manually
   * invoked from the button-level validation handler, otherwise an exception
   * will be thrown.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);

    $violations = $entity->validate();

    // Remove violations of inaccessible fields.
    $violations->filterByFieldAccess($this->currentUser());

    // In case a field-level submit button is clicked, for example the 'Add
    // another item' button for multi-value fields or the 'Upload' button for a
    // File or an Image field, make sure that we only keep violations for that
    // specific field.
    $edited_fields = [];
    if ($limit_validation_errors = $form_state->getLimitValidationErrors()) {
      foreach ($limit_validation_errors as $section) {
        $field_name = reset($section);
        if ($entity->hasField($field_name)) {
          $edited_fields[] = $field_name;
        }
      }
      $edited_fields = array_unique($edited_fields);
    }
    else {
      $edited_fields = $this->getEditedFieldNames($form_state);
    }

    // Remove violations for fields that are not edited.
    $violations->filterByFields(array_diff(array_keys($entity->getFieldDefinitions()), $edited_fields));

    $this->flagViolations($violations, $form, $form_state);

    // The entity was validated.
    $entity->setValidationRequired(FALSE);
    $form_state->setTemporaryValue('entity_validated', TRUE);

    return $entity;
  }

  /**
   * Gets the names of all fields edited in the form.
   *
   * If a custom entity form adds some fields to the form (i.e. without using
   * the form display), it needs to add its fields here and override
   * flagViolations() for displaying the violations.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string[]
   *   An array of field names.
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_keys($this->getFormDisplay($form_state)->getComponents());
  }

  /**
   * Flags violations for the current form.
   *
   * If a custom entity form adds some fields to the form (i.e. without using
   * the form display), it needs to add its fields to array returned by
   * getEditedFieldNames() and overwrite this method in order to show any
   * violations for those fields; e.g.:
   * @code
   * foreach ($violations->getByField('name') as $violation) {
   *   $form_state->setErrorByName('name', $violation->getMessage());
   * }
   * parent::flagViolations($violations, $form, $form_state);
   * @endcode
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   The violations to flag.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Flag entity level violations.
    foreach ($violations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setErrorByName(str_replace('.', '][', $violation->getPropertyPath()), $violation->getMessage());
    }
    // Let the form display flag violations of its fields.
    $this->getFormDisplay($form_state)->flagWidgetsErrorsFromViolations($violations, $form, $form_state);
  }

  /**
   * Initializes the form state and the entity before the first form build.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function init(FormStateInterface $form_state) {
    // Ensure we act on the translation object corresponding to the current form
    // language.
    $this->initFormLangcodes($form_state);
    $langcode = $this->getFormLangcode($form_state);
    $this->entity = $this->entity->hasTranslation($langcode) ? $this->entity->getTranslation($langcode) : $this->entity->addTranslation($langcode);

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->getOperation());
    $this->setFormDisplay($form_display, $form_state);

    parent::init($form_state);
  }

  /**
   * Initializes form language code values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function initFormLangcodes(FormStateInterface $form_state) {
    // Store the entity default language to allow checking whether the form is
    // dealing with the original entity or a translation.
    if (!$form_state->has('entity_default_langcode')) {
      $form_state->set('entity_default_langcode', $this->entity->getUntranslated()->language()->getId());
    }
    // This value might have been explicitly populated to work with a particular
    // entity translation. If not we fall back to the most proper language based
    // on contextual information.
    if (!$form_state->has('langcode')) {
      // Imply a 'view' operation to ensure users edit entities in the same
      // language they are displayed. This allows to keep contextual editing
      // working also for multilingual entities.
      $form_state->set('langcode', $this->entityRepository->getTranslationFromContext($this->entity)->language()->getId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormLangcode(FormStateInterface $form_state) {
    $this->initFormLangcodes($form_state);
    return $form_state->get('langcode');
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultFormLangcode(FormStateInterface $form_state) {
    $this->initFormLangcodes($form_state);
    return $form_state->get('langcode') == $form_state->get('entity_default_langcode');
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // First, extract values from widgets.
    $extracted = $this->getFormDisplay($form_state)->extractFormValues($entity, $form, $form_state);

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($form_state->getValues() as $name => $values) {
      if ($entity->hasField($name) && !isset($extracted[$name])) {
        $entity->set($name, $values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('form_display');
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state) {
    $form_state->set('form_display', $form_display);
    return $this;
  }

  /**
   * Updates the form language to reflect any change to the entity language.
   *
   * There are use cases for modules to act both before and after form language
   * being updated, thus the update is performed through an entity builder
   * callback, which allows to support both cases.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::form()
   */
  public function updateFormLangcode($entity_type_id, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $langcode = $entity->language()->getId();
    $form_state->set('langcode', $langcode);

    // If this is the original entity language, also update the default
    // langcode.
    if ($langcode == $entity->getUntranslated()->language()->getId()) {
      $form_state->set('entity_default_langcode', $langcode);
    }
  }

  /**
   * Updates the changed time of the entity.
   *
   * Applies only if the entity implements the EntityChangedInterface.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity updated with the submitted values.
   */
  public function updateChangedTime(EntityInterface $entity) {
    if ($entity instanceof EntityChangedInterface) {
      $entity->setChangedTime($this->time->getRequestTime());
    }
  }

  /**
   * Add revision form fields if the entity enabled the UI.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function addRevisionableFormFields(array &$form) {
    /** @var ContentEntityTypeInterface $entity_type */
    $entity_type = $this->entity->getEntityType();

    $new_revision_default = $this->getNewRevisionDefault();

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $new_revision_default,
      '#group' => 'advanced',
      '#weight' => 20,
      '#access' => $new_revision_default || $this->entity->get($entity_type->getKey('revision'))->access('update'),
      '#optional' => TRUE,
      '#attributes' => [
        'class' => ['entity-content-form-revision-information'],
      ],
      '#attached' => [
        'library' => ['core/drupal.entity-form'],
      ],
    ];

    $form['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $new_revision_default,
      '#access' => !$this->entity->isNew() && $this->entity->get($entity_type->getKey('revision'))->access('update'),
      '#group' => 'revision_information',
    ];
    // Get log message field's key from definition.
    $log_message_field = $entity_type->getRevisionMetadataKey('revision_log_message');
    if ($log_message_field && isset($form[$log_message_field])) {
      $form[$log_message_field] += [
        '#group' => 'revision_information',
        '#states' => [
          'visible' => [
            ':input[name="revision"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
  }

  /**
   * Should new revisions created on default.
   *
   * @return bool
   *   New revision on default.
   */
  protected function getNewRevisionDefault() {
    $new_revision_default = FALSE;
    $bundle_entity = $this->getBundleEntity();
    if ($bundle_entity instanceof RevisionableEntityBundleInterface) {
      // Always use the default revision setting.
      $new_revision_default = $bundle_entity->shouldCreateNewRevision();
    }
    return $new_revision_default;
  }

  /**
   * Checks whether the revision form fields should be added to the form.
   *
   * @return bool
   *   TRUE if the form field should be added, FALSE otherwise.
   */
  protected function showRevisionUi() {
    return $this->entity->getEntityType()->showRevisionUi();
  }

}
