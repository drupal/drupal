<?php

/**
 * @file
 * Contains \Drupal\system\Form\ImageToolkitForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\system\SystemConfigFormBase;
use Drupal\system\Plugin\ImageToolkitManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures image toolkit settings for this site.
 */
class ImageToolkitForm extends SystemConfigFormBase {

  /**
   * An array containing currently available toolkits.
   *
   * @var array
   */
  protected $availableToolkits = array();

  /**
   * Constructs a ImageToolkitForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context used for this configuration object.
   * @param \Drupal\system\Plugin\ImageToolkitManager $manager
   *   The image toolkit plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, ContextInterface $context, ImageToolkitManager $manager) {
    parent::__construct($config_factory, $context);

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
      $container->get('config.context.free'),
      $container->get('image.toolkit.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_image_toolkit_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $current_toolkit = $this->configFactory->get('system.image')->get('toolkit');

    $form['image_toolkit'] = array(
      '#type' => 'radios',
      '#title' => t('Select an image processing toolkit'),
      '#default_value' => $current_toolkit,
      '#options' => array(),
    );

    // If we have available toolkits, allow the user to select the image toolkit
    // to use and load the settings forms.
    foreach ($this->availableToolkits as $id => $toolkit) {
      $definition = $toolkit->getPluginDefinition();
      $form['image_toolkit']['#options'][$id] = $definition['title'];
      $form['image_toolkit_settings'][$id] = array(
        '#type' => 'fieldset',
        '#title' => t('@toolkit settings', array('@toolkit' => $definition['title'])),
        '#collapsible' => TRUE,
        '#collapsed' => ($id == $current_toolkit) ? FALSE : TRUE,
        '#tree' => TRUE,
      );
      $form['image_toolkit_settings'][$id] += $toolkit->settingsForm();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('system.image')
      ->set('toolkit', $form_state['values']['image_toolkit'])
      ->save();

    // Call the form submit handler for each of the toolkits.
    // Get the toolkit settings forms.
    foreach ($this->availableToolkits as $id => $toolkit) {
      $toolkit->settingsFormSubmit($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
