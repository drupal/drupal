<?php

/**
 * @file
 * Contains \Drupal\Core\ImageToolkit\ImageToolkitBase.
 */

namespace Drupal\Core\ImageToolkit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;

abstract class ImageToolkitBase extends PluginBase implements ImageToolkitInterface {

  /**
   * Image object this toolkit instance is tied to.
   *
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected $image;

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
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   *   The toolkit operation manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->operationManager = $operation_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function setImage(ImageInterface $image) {
    if ($this->image) {
      throw new \BadMethodCallException(__METHOD__ . '() may only be called once');
    }
    $this->image = $image;
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    return $this->image;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    return array();
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
  public function apply($operation, array $arguments = array()) {
    try {
      // Get the plugin to use for the operation and apply the operation.
      return $this->getToolkitOperation($operation)->apply($arguments);
    }
    catch (PluginNotFoundException $e) {
      $this->logger->error("The selected image handling toolkit '@toolkit' can not process operation '@operation'.", array('@toolkit' => $this->getPluginId(), '@operation' => $operation));
      return FALSE;
    }
    catch (\InvalidArgumentException $e) {
      $this->logger->warning($e->getMessage(), array());
      return FALSE;
    }
  }

}
