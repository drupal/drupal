<?php

namespace Drupal\image_test\Plugin\ImageToolkit;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Test toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "test",
 *   title = @Translation("A dummy toolkit that works")
 * )
 */
class TestToolkit extends ImageToolkitBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Image type represented by a PHP IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG).
   *
   * @var int
   */
  protected $type;

  /**
   * The width of the image.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of the image.
   *
   * @var int
   */
  protected $height;

  /**
   * Constructs a TestToolkit object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   *   The toolkit operation manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $operation_manager, $logger, $config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.toolkit.operation.manager'),
      $container->get('logger.channel.image'),
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->logCall('settings', func_get_args());
    $form['test_parameter'] = [
      '#type' => 'number',
      '#title' => $this->t('Test toolkit parameter'),
      '#description' => $this->t('A toolkit parameter for testing purposes.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $this->configFactory->getEditable('system.image.test_toolkit')->get('test_parameter', FALSE),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['test', 'test_parameter']) == 0) {
      $form_state->setErrorByName('test][test_parameter', $this->t('Test parameter should be different from 0.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('system.image.test_toolkit')
      ->set('test_parameter', $form_state->getValue(['test', 'test_parameter']))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return isset($this->type);
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    $this->logCall('parseFile', func_get_args());
    $data = @getimagesize($this->getSource());
    if ($data && in_array($data[2], static::supportedTypes())) {
      $this->setType($data[2]);
      $this->width = $data[0];
      $this->height = $data[1];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination) {
    $this->logCall('save', func_get_args());
    // Return false so that image_save() doesn't try to chmod the destination
    // file that we didn't bother to create.
    return FALSE;
  }

  /**
   * Stores the values passed to a toolkit call.
   *
   * @param string $op
   *   One of the toolkit methods 'parseFile', 'save', 'settings', or 'apply'.
   * @param array $args
   *   Values passed to hook.
   *
   * @see \Drupal\Tests\system\Functional\Image\ToolkitTestBase::imageTestReset()
   * @see \Drupal\Tests\system\Functional\Image\ToolkitTestBase::imageTestGetAllCalls()
   */
  protected function logCall($op, $args) {
    $results = $this->state->get('image_test.results') ?: [];
    $results[$op][] = $args;
    // A call to apply is also logged under its operation name whereby the
    // array of arguments are logged as separate arguments, this because at the
    // ImageInterface level we still have methods named after the operations.
    if ($op === 'apply') {
      $operation = array_shift($args);
      $results[$operation][] = array_values(reset($args));
    }
    $this->state->set('image_test.results', $results);
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Returns the type of the image.
   *
   * @return int
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the PHP type of the image.
   *
   * @param int $type
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   *
   * @return $this
   */
  public function setType($type) {
    if (in_array($type, static::supportedTypes())) {
      $this->type = $type;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->getType() ? image_type_to_mime_type($this->getType()) : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedExtensions() {
    $extensions = [];
    foreach (static::supportedTypes() as $image_type) {
      $extensions[] = Unicode::strtolower(image_type_to_extension($image_type, FALSE));
    }
    return $extensions;
  }

  /**
   * Returns a list of image types supported by the toolkit.
   *
   * @return array
   *   An array of available image types. An image type is represented by a PHP
   *   IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   */
  protected static function supportedTypes() {
    return [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF];
  }

  /**
   * {@inheritdoc}
   */
  public function apply($operation, array $arguments = []) {
    $this->logCall('apply', func_get_args());
    return TRUE;
  }

}
