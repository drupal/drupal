<?php

/**
 * @file
 * Contains \Drupal\system\Form\ImageToolkitForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures image toolkit settings for this site.
 */
class ImageToolkitForm extends ConfigFormBase {

  /**
   * An array containing currently available toolkits.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface[]
   */
  protected $availableToolkits = array();

  /**
   * Constructs a ImageToolkitForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitManager $manager
   *   The image toolkit plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ImageToolkitManager $manager) {
    parent::__construct($config_factory);

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
    $current_toolkit = $this->config('system.image')->get('toolkit');

    $form['image_toolkit'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Select an image processing toolkit'),
      '#default_value' => $current_toolkit,
      '#options' => array(),
    );

    // If we have more than one image toolkit, allow the user to select the one
    // to use, and load each of the toolkits' settings form.
    foreach ($this->availableToolkits as $id => $toolkit) {
      $definition = $toolkit->getPluginDefinition();
      $form['image_toolkit']['#options'][$id] = $definition['title'];
      $form['image_toolkit_settings'][$id] = array(
        '#type' => 'details',
        '#title' => $this->t('@toolkit settings', array('@toolkit' => $definition['title'])),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#states' => array(
          'visible' => array(
            ':radio[name="image_toolkit"]' => array('value' => $id),
          ),
        ),
      );
      $form['image_toolkit_settings'][$id] += $toolkit->buildConfigurationForm(array(), $form_state);
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
    $this->config('system.image')
      ->set('toolkit', $form_state->getValue('image_toolkit'))
      ->save();

    // Call the form submit handler for each of the toolkits.
    foreach ($this->availableToolkits as $toolkit) {
      $toolkit->submitConfigurationForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
