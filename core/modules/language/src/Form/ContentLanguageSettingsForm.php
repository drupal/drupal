<?php

namespace Drupal\language\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the content language settings for this site.
 *
 * @internal
 */
class ContentLanguageSettingsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * If this validator can handle multiple arguments.
   *
   * @var bool
   */
  protected $multipleCapable = TRUE;

  /**
   * Constructs an \Drupal\views\Plugin\views\argument_validator\Entity object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_content_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $labels = [];
    $default = [];

    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    $language_configuration = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface || !$entity_type->hasKey('langcode') || !isset($bundles[$entity_type_id])) {
        continue;
      }
      $labels[$entity_type_id] = $entity_type->getLabel() ?: $entity_type_id;
      $default[$entity_type_id] = FALSE;

      // Check whether we have any custom setting.
      foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
        $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
        if (!$config->isDefaultConfiguration()) {
          $default[$entity_type_id] = $entity_type_id;
        }
        $language_configuration[$entity_type_id][$bundle] = $config;
      }
    }

    asort($labels);

    $form = [
      '#labels' => $labels,
      '#attached' => [
        'library' => [
          'language/drupal.language.admin',
        ],
      ],
      '#attributes' => [
        'class' => 'language-content-settings-form',
      ],
    ];

    $form['entity_types'] = [
      '#title' => $this->t('Custom language settings'),
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $default,
    ];

    $form['settings'] = ['#tree' => TRUE];

    foreach ($labels as $entity_type_id => $label) {
      $entity_type = $entity_types[$entity_type_id];

      $form['settings'][$entity_type_id] = [
        '#title' => $label,
        '#type' => 'container',
        '#entity_type' => $entity_type_id,
        '#theme' => 'language_content_settings_table',
        '#bundle_label' => $entity_type->getBundleLabel() ?: $label,
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
        $form['settings'][$entity_type_id][$bundle]['settings'] = [
          '#type' => 'item',
          '#label' => $bundle_info['label'],
          'language' => [
            '#type' => 'language_configuration',
            '#entity_information' => [
              'entity_type' => $entity_type_id,
              'bundle' => $bundle,
            ],
            '#default_value' => $language_configuration[$entity_type_id][$bundle],
          ],
        ];
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_types = $form_state->getValue('entity_types');
    foreach ($form_state->getValue('settings') as $entity_type => $entity_settings) {
      foreach ($entity_settings as $bundle => $bundle_settings) {
        $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type, $bundle);
        if (empty($entity_types[$entity_type])) {
          $bundle_settings['settings']['language']['language_alterable'] = FALSE;
        }
        $config->setDefaultLangcode($bundle_settings['settings']['language']['langcode'])
          ->setLanguageAlterable($bundle_settings['settings']['language']['language_alterable'])
          ->save();
      }
    }
    $this->messenger()->addStatus($this->t('Settings successfully updated.'));
  }

}
