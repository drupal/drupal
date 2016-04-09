<?php

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access to the user data service.
 *
 * @ingroup views_field_handlers
 *
 * @see \Drupal\user\UserDataInterface
 *
 * @ViewsField("user_data")
 */
class UserData extends FieldPluginBase {

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('user.data'), $container->get('module_handler'));
  }

  /**
   * Constructs a UserData object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userData = $user_data;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['data_module'] = array('default' => '');
    $options['data_name'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $modules = $this->moduleHandler->getModuleList();
    $names = array();
    foreach (array_keys($modules) as $name) {
      $names[$name] = $this->moduleHandler->getName($name);
    }

    $form['data_module'] = array(
      '#title' => $this->t('Module name'),
      '#type' => 'select',
      '#description' => $this->t('The module which sets this user data.'),
      '#default_value' => $this->options['data_module'],
      '#options' => $names,
    );

    $form['data_name'] = array(
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#description' => $this->t('The name of the data key.'),
      '#default_value' => $this->options['data_name'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values);
    $data = $this->userData->get($this->options['data_module'], $uid, $this->options['data_name']);

    // Don't sanitize if no value was found.
    if (isset($data)) {
      return $this->sanitizeValue($data);
    }
  }

}
