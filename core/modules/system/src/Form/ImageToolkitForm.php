<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures image toolkit settings for this site.
 *
 * @internal
 */
class ImageToolkitForm extends ConfigFormBase {

  /**
   * An array containing currently available toolkits.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface[]
   */
  protected $availableToolkits = [];

  /**
   * Constructs an ImageToolkitForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitManager $manager
   *   The image toolkit plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, ImageToolkitManager $manager) {
    parent::__construct($config_factory, $typedConfigManager);

    foreach ($manager->getAvailableToolkits() as $id => $definition) {
      $this->availableToolkits[$id] = $manager->createInstance($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('image.toolkit.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_image_toolkit_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.image'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['image_toolkit'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an image processing toolkit'),
      '#config_target' => 'system.image:toolkit',
      '#options' => [],
    ];

    // If we have more than one image toolkit, allow the user to select the one
    // to use, and load each of the toolkits' settings form.
    foreach ($this->availableToolkits as $id => $toolkit) {
      $definition = $toolkit->getPluginDefinition();
      $form['image_toolkit']['#options'][$id] = $definition['title'];
      $form['image_toolkit_settings'][$id] = [
        '#type' => 'details',
        '#title' => $this->t('@toolkit settings', ['@toolkit' => $definition['title']]),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            ':radio[name="image_toolkit"]' => ['value' => $id],
          ],
        ],
      ];
      $form['image_toolkit_settings'][$id] += $toolkit->buildConfigurationForm([], $form_state);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Call the form validation handler for each of the toolkits.
    foreach ($this->availableToolkits as $toolkit) {
      $toolkit->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the form submit handler for each of the toolkits.
    foreach ($this->availableToolkits as $toolkit) {
      $toolkit->submitConfigurationForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
