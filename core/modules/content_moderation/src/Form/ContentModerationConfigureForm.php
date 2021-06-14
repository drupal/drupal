<?php

namespace Drupal\content_moderation\Form;

use Drupal\Component\Serialization\Json;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\workflows\Plugin\WorkflowTypeConfigureFormBase;
use Drupal\workflows\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The content moderation WorkflowType configuration form.
 *
 * @see \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration
 */
class ContentModerationConfigureForm extends WorkflowTypeConfigureFormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The entity type type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Create an instance of ContentModerationConfigureForm.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModerationInformationInterface $moderationInformation, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moderationInfo = $moderationInformation;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $workflow = $form_state->getFormObject()->getEntity();

    $header = [
      'type' => $this->t('Items'),
      'operations' => $this->t('Operations'),
    ];
    $form['entity_types_container'] = [
      '#type' => 'details',
      '#title' => $this->t('This workflow applies to:'),
      '#open' => TRUE,
    ];
    $form['entity_types_container']['entity_types'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no entity types.'),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if (!$this->moderationInfo->canModerateEntitiesOfEntityType($entity_type)) {
        continue;
      }

      $selected_bundles = [];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type->id()) as $bundle_id => $bundle) {
        if ($this->workflowType->appliesToEntityTypeAndBundle($entity_type->id(), $bundle_id)) {
          $selected_bundles[$bundle_id] = $bundle['label'];
        }
      }

      $selected_bundles_list = [
        '#theme' => 'item_list',
        '#items' => $selected_bundles,
        '#context' => ['list_style' => 'comma-list'],
        '#empty' => $this->t('none'),
      ];
      $form['entity_types_container']['entity_types'][$entity_type->id()] = [
        'type' => [
          '#type' => 'inline_template',
          '#template' => '<strong>{{ label }}</strong></br><span id="selected-{{ entity_type_id }}">{{ selected_bundles }}</span>',
          '#context' => [
            'label' => $this->t('@bundle types', ['@bundle' => $entity_type->getLabel()]),
            'entity_type_id' => $entity_type->id(),
            'selected_bundles' => $selected_bundles_list,
          ],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'select' => [
              'title' => $this->t('Select'),
              'url' => Url::fromRoute('content_moderation.workflow_type_edit_form', ['workflow' => $workflow->id(), 'entity_type_id' => $entity_type->id()]),
              'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    $workflow_type_configuration = $this->workflowType->getConfiguration();
    $form['workflow_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Workflow Settings'),
      '#open' => TRUE,
    ];
    $form['workflow_settings']['default_moderation_state'] = [
      '#title' => $this->t('Default moderation state'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => array_map([State::class, 'labelCallback'], $this->workflowType->getStates()),
      '#description' => $this->t('Select the state that new content will be assigned. This state will appear as the default in content forms and the available target states will be based on the transitions available from this state.'),
      '#default_value' => isset($workflow_type_configuration['default_moderation_state']) ? $workflow_type_configuration['default_moderation_state'] : 'draft',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->workflowType->getConfiguration();
    $configuration['default_moderation_state'] = $form_state->getValue(['workflow_settings', 'default_moderation_state']);
    $this->workflowType->setConfiguration($configuration);
  }

}
