<?php

/**
 * @file
 * Contains \Drupal\Core\Form\ConfigFormBase.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for implementing system configuration forms.
 */
abstract class ConfigFormBase extends FormBase {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context to use.
   */
  public function __construct(ConfigFactory $config_factory, ContextInterface $context) {
    $this->configFactory = $config_factory;
    $this->configFactory->enterContext($context);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function config($name) {
    if (!$this->configFactory) {
      $container = $this->container();
      $this->configFactory = $container->get('config.factory');
      $this->configFactory->enterContext($container->get('config.context.free'));
    }
    return $this->configFactory->get($name);
  }
}
