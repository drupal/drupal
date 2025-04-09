<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the generic base class for entity display mode forms.
 */
abstract class EntityDisplayModeFormBase extends EntityForm {

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The display context. Either 'view' or 'form'.
   *
   * @var string
   */
  protected string $displayContext;

  /**
   * The entity type for which the display mode is being created or edited.
   *
   * @var string|null
   */
  protected ?string $targetEntityTypeId;

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entityType = $this->entityTypeManager->getDefinition($this->entity->getEntityTypeId());
    $this->displayContext = str_replace(['entity_', '_mode'], '', $this->entityType->id());
  }

  /**
   * Constructs a EntityDisplayModeFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle service.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(protected EntityTypeBundleInfoInterface $entityTypeBundleInfo, protected EntityDisplayRepositoryInterface $entityDisplayRepository) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    if (!$entity_type_id && !$this->entity->isNew()) {
      $entity_type_id = $this->entity->getTargetType();
    }
    $this->targetEntityTypeId = $entity_type_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 100,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('This text will be displayed on the @mode_label list page.', [
        '@mode_label' => $this->entity->getEntityType()->getPluralLabel(),
      ]),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#field_prefix' => $this->entity->isNew() ? $this->entity->getTargetType() . '.' : '',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ],
    ];
    $bundle_info_service = $this->entityTypeBundleInfo;
    $bundles = $bundle_info_service->getAllBundleInfo();
    $definition = $this->entityTypeManager->getDefinition($this->entity->isNew() ? $this->targetEntityTypeId : $this->entity->getTargetType());

    $bundles_by_entity = [];
    $defaults = [];
    foreach (array_keys($bundles[$definition->id()]) as $bundle) {
      $bundles_by_entity[$bundle] = $bundles[$definition->id()][$bundle]['label'];
      // Determine default display modes.
      if (!$this->entity->isNew()) {
        [, $display_mode_name] = explode('.', $this->entity->id());
        if ($this->getDisplayByContext($bundle, $display_mode_name)) {
          $defaults[$bundle] = $bundle;
        }
      }
    }

    $form['bundles_by_entity'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable this @display-mode for the following @bundle-label types:', ['@display-mode' => $this->entityType->getSingularLabel(), '@bundle-label' => $definition->getLabel()]),
      '#description' => $this->t('This @display-mode will still be available for the rest of the @bundle-label types if not checked here, but it will not be enabled by default.', ['@bundle-label' => $definition->getLabel(), '@display-mode' => $this->entityType->getSingularLabel()]),
      '#options' => $bundles_by_entity,
      '#default_value' => $defaults,
    ];

    return $form;
  }

  /**
   * Determines if the display mode already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the display mode exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    // Do not allow to add internal 'default' view mode.
    if ($entity_id == 'default') {
      return TRUE;
    }
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($this->entity->getEntityTypeId());
    return (bool) $storage
      ->getQuery()
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Saved the %label @entity-type.', ['%label' => $this->entity->label(), '@entity-type' => $this->entityType->getSingularLabel()]));
    $this->entity->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    [, $display_mode_name] = explode('.', $form_state->getValue('id'));
    $target_entity_id = $this->targetEntityTypeId;

    foreach ($form_state->getValue('bundles_by_entity') as $bundle => $value) {
      if (!empty($value)) {
        // Add a new entity view/form display if it doesn't already exist.
        if (!$this->getDisplayByContext($bundle, $display_mode_name)) {
          $display = $this->getEntityDisplay($target_entity_id, $bundle, 'default')->createCopy($display_mode_name);
          $display->save();
        }

        // This message is still helpful, even if the view/form display hasn't
        // changed, so we keep it outside the above check.
        $url = $this->getOverviewUrl($display_mode_name, $value);

        $bundle_info_service = $this->entityTypeBundleInfo;
        $bundles = $bundle_info_service->getAllBundleInfo();
        $bundle_label = $bundles[$target_entity_id][$bundle]['label'];
        $display_mode_label = $form_state->getValue('label');

        $this->messenger()->addStatus($this->t('<a href=":url">Configure the %display_mode_label %mode mode for %bundle_label</a>.', ['%mode' => $this->displayContext, '%display_mode_label' => $display_mode_label, '%bundle_label' => $bundle_label, ':url' => $url->toString()]));
      }
      else {
        // The view/form display has been unchecked, so we need to delete this.
        // There's no confirmation of deleting the view/form display on the node
        // content type forms either, so we match that behavior.
        if ($display = $this->getDisplayByContext($bundle, $display_mode_name)) {
          $display->delete();
        }
      }

    }
  }

  /**
   * Returns an entity display object to be used by this form.
   *
   * @param string $entity_type_id
   *   The target entity type ID of the entity display.
   * @param string $bundle
   *   The target bundle of the entity display.
   * @param string $mode
   *   A view or form mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   An entity display.
   */
  private function getEntityDisplay($entity_type_id, $bundle, $mode) {
    return match($this->displayContext) {
      'view' => $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $mode),
      'form' => $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle, $mode),
    };
  }

  /**
   * Returns the Url object for a specific entity (form) display edit form.
   *
   * @param string $mode
   *   The form or view mode.
   * @param string $bundle
   *   The entity bundle name.
   *
   * @return \Drupal\Core\Url
   *   A Url object for the overview route.
   */
  private function getOverviewUrl($mode, $bundle): Url {
    $entity_type = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    return match($this->displayContext) {
      'view' => Url::fromRoute('entity.entity_view_display.' . $this->targetEntityTypeId . '.view_mode', [
        'view_mode_name' => $mode,
      ] + FieldUI::getRouteBundleParameter($entity_type, $bundle)),
      'form' => Url::fromRoute('entity.entity_form_display.' . $this->targetEntityTypeId . '.form_mode', [
        'form_mode_name' => $mode,
      ] + FieldUI::getRouteBundleParameter($entity_type, $bundle)),
    };
  }

  /**
   * Load the view display for a given bundle and view mode name.
   *
   * @param string $bundle
   *   The entity bundle to load the view display for.
   * @param string $view_mode_name
   *   The view mode name such as "full_content" to load the view display for.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
   *   Returns the view display, or NULL if one does not exist.
   */
  private function getViewDisplay(string $bundle, string $view_mode_name): ?EntityViewDisplayInterface {
    $view_mode_id = $this->targetEntityTypeId . '.' . $bundle . '.' . $view_mode_name;
    return $this->entityTypeManager->getStorage('entity_view_display')->load($view_mode_id);
  }

  /**
   * Load the form display for a given bundle and form mode name.
   *
   * @param string $bundle
   *   The entity bundle to load the form display for.
   * @param string $form_mode_name
   *   The form mode name to load the form display for.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface|null
   *   Returns the form display, or NULL if one does not exist.
   */
  private function getFormDisplay(string $bundle, string $form_mode_name): ?EntityFormDisplayInterface {
    $form_mode_id = $this->targetEntityTypeId . '.' . $bundle . '.' . $form_mode_name;
    return $this->entityTypeManager->getStorage('entity_form_display')->load($form_mode_id);
  }

  /**
   * Returns View or Form display based on display context.
   *
   * @param string $bundle
   *   The entity bundle to load the display for.
   * @param string $display_mode_name
   *   The display mode name to load the display for.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface|\Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
   *   Returns the display, or NULL if one does not exist.
   */
  private function getDisplayByContext(string $bundle, string $display_mode_name): EntityFormDisplayInterface|EntityViewDisplayInterface|null {
    return match($this->displayContext) {
      'view' => $this->getViewDisplay($bundle, $display_mode_name),
      'form' => $this->getFormDisplay($bundle, $display_mode_name),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    // Config schema dictates that the description value
    // cannot be empty string. So, if it is empty, make it NULL.
    if ($form_state->hasValue('description') && trim($form_state->getValue('description')) === '') {
      $form_state->setValue('description', NULL);
    }
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

}
