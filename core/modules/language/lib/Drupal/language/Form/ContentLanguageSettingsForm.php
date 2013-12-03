<?php

/**
 * @file
 * Contains \Drupal\language\Form\ContentLanguageSettingsForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the content language settings for this site.
 */
class ContentLanguageSettingsForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContentLanguageSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context to use.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactory $config_factory, ContextInterface $context, EntityManagerInterface $entity_manager) {
    parent::__construct($config_factory, $context);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free'),
      $container->get('entity.manager')
    );
  }

  /**
   * Return a list of entity types for which language settings are supported.
   *
   * @return array
   *   A list of entity types which are translatable.
   */
  protected function entitySupported() {
    $supported = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type => $info) {
      if (!empty($info['translatable'])) {
        $supported[$entity_type] = $entity_type;
      }
    }
    return $supported;
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
  public function buildForm(array $form, array &$form_state) {
    $entity_info = $this->entityManager->getDefinitions();
    $labels = array();
    $default = array();

    $bundles = entity_get_bundles();
    $language_configuration = array();
    foreach ($this->entitySupported() as $entity_type) {
      $labels[$entity_type] = isset($entity_info[$entity_type]['label']) ? $entity_info[$entity_type]['label'] : $entity_type;
      $default[$entity_type] = FALSE;

      // Check whether we have any custom setting.
      foreach ($bundles as $bundle => $bundle_info) {
        $conf = language_get_default_configuration($entity_type, $bundle);
        if (!empty($conf['language_show']) || $conf['langcode'] != 'site_default') {
          $default[$entity_type] = $entity_type;
        }
        $language_configuration[$entity_type][$bundle] = $conf;
      }
    }

    asort($labels);

    $form = array(
      '#labels' => $labels,
      '#attached' => array(
        'library' => array(
          array('language', 'drupal.language.admin'),
        ),
      ),
    );

    $form['entity_types'] = array(
      '#title' => $this->t('Custom language settings'),
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $default,
    );

    $form['settings'] = array('#tree' => TRUE);

    foreach ($labels as $entity_type => $label) {
      $info = $entity_info[$entity_type];

      $form['settings'][$entity_type] = array(
        '#title' => $label,
        '#type' => 'container',
        '#entity_type' => $entity_type,
        '#theme' => 'language_content_settings_table',
        '#bundle_label' => isset($info['bundle_label']) ? $info['bundle_label'] : $label,
        '#states' => array(
          'visible' => array(
            ':input[name="entity_types[' . $entity_type . ']"]' => array('checked' => TRUE),
          ),
        ),
      );

      foreach ($bundles as $bundle => $bundle_info) {
        $form['settings'][$entity_type][$bundle]['settings'] = array(
          '#type' => 'item',
          '#label' => $bundle_info['label'],
          'language' => array(
            '#type' => 'language_configuration',
            '#entity_information' => array(
              'entity_type' => $entity_type,
              'bundle' => $bundle,
            ),
            '#default_value' => $language_configuration[$entity_type][$bundle],
          ),
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->configFactory->get('language.settings');
    foreach ($form_state['values']['settings'] as $entity_type => $entity_settings) {
      foreach ($entity_settings as $bundle => $bundle_settings) {
          $config->set(language_get_default_configuration_settings_key($entity_type, $bundle),
            array(
              'langcode' => $bundle_settings['settings']['language']['langcode'],
              'language_show' => $bundle_settings['settings']['langcode']['language_show'],
            )
          );
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
