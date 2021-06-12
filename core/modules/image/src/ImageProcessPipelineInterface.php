<?php

namespace Drupal\image;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an interface defining an ImageProcessPipeline plugin.
 */
interface ImageProcessPipelineInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Sets a pipeline variable to a specified value.
   *
   * @param string $variable
   *   The variable to set.
   * @param mixed $value
   *   The value to set.
   *
   * @return self
   */
  public function setVariable(string $variable, $value): ImageProcessPipelineInterface;

  /**
   * Returns the value of a pipeline variable.
   *
   * @param string $variable
   *   The variable to get.
   *
   * @return mixed
   *   The value of the variable.
   *
   * @throws \Drupal\image\ImageProcessException
   *   If the variable is not set.
   */
  public function getVariable(string $variable);

  /**
   * Returns whether a pipeline variable is set.
   *
   * @param string $variable
   *   The variable to check.
   *
   * @return bool
   *   TRUE if the variable is set, FALSE otherwise.
   */
  public function hasVariable(string $variable): bool;

  /**
   * Deletes a pipeline variable.
   *
   * @param string $variable
   *   The variable to delete.
   *
   * @return self
   */
  public function deleteVariable(string $variable): ImageProcessPipelineInterface;

  /**
   * Sets the Image object to be manipulated.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The ImageInterface object to be derived.
   *
   * @return self
   */
  public function setImage(ImageInterface $image): ImageProcessPipelineInterface;

  /**
   * Returns the current Image object.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The ImageInterface object to be derived.
   */
  public function getImage(): ImageInterface;

  /**
   * Returns whether the Image object is set.
   *
   * @return bool
   *   TRUE if the Image is set, FALSE otherwise.
   */
  public function hasImage(): bool;

  /**
   * Dispatches an event to be executed on the pipeline.
   *
   * The event envelope will contain the pipeline itself as the subject, and any
   * additional argument specified.
   *
   * @param string $event
   *   The event identifier.
   * @param array $arguments
   *   (Optional) Additional arguments for the event.
   *
   * @return self
   */
  public function dispatch(string $event, array $arguments = []): ImageProcessPipelineInterface;

}
