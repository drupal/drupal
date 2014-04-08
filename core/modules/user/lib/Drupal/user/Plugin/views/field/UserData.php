<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\views\field\UserData.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('user.data'));
  }

  /**
   * Constructs a UserData object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userData = $user_data;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['data_module'] = array('default' => '');
    $options['data_name'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::defineOptions().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $modules = system_get_info('module');
    $names = array();
    foreach ($modules as $name => $module) {
      $names[$name] = $module['name'];
    }

    $form['data_module'] = array(
      '#title' => t('Module name'),
      '#type' => 'select',
      '#description' => t('The module which sets this user data.'),
      '#default_value' => $this->options['data_module'],
      '#options' => $names,
    );

    $form['data_name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#description' => t('The name of the data key.'),
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
