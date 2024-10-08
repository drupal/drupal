<?php

namespace Drupal\Core\ImageToolkit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;

/**
 * Provides a base class for image toolkit plugins.
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkit
 * @see \Drupal\Core\ImageToolkit\ImageToolkitInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitManager
 * @see plugin_api
 */
abstract class ImageToolkitBase extends PluginBase implements ImageToolkitInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Path of the image file.
   *
   * @var string
   */
  protected $source = '';

  /**
   * The image toolkit operation manager.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface
   */
  protected $operationManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an ImageToolkitBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   *   The toolkit operation manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->operationManager = $operation_manager;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    // If a previous image has been loaded, there is no way to know if the
    // toolkit implementation needs to perform any additional actions like
    // freeing memory. Therefore, the source image cannot be changed once set.
    if ($this->source) {
      throw new \BadMethodCallException(__METHOD__ . '() may only be called once');
    }
    $this->source = $source;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    return [];
  }

  /**
   * Gets a toolkit operation plugin instance.
   *
   * @param string $operation
   *   The toolkit operation requested.
   *
   * @return \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
   *   An instance of the requested toolkit operation plugin.
   */
  protected function getToolkitOperation($operation) {
    return $this->operationManager->getToolkitOperation($this, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function apply($operation, array $arguments = []) {
    try {
      // Get the plugin to use for the operation and apply the operation.
      return $this->getToolkitOperation($operation)->apply($arguments);
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error("The selected image handling toolkit '@toolkit' can not process operation '@operation'.", ['@toolkit' => $this->getPluginId(), '@operation' => $operation]);
      return FALSE;
    }
    catch (\Throwable $t) {
      $this->logger->warning("The image toolkit '@toolkit' failed processing '@operation' for image '@image'. Reported error: @class - @message", [
        '@toolkit' => $this->getPluginId(),
        '@operation' => $operation,
        '@image' => $this->getSource(),
        '@class' => get_class($t),
        '@message' => $t->getMessage(),
      ]);
      return FALSE;
    }
  }

}
