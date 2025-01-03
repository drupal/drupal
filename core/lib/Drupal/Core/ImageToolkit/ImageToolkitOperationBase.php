<?php

namespace Drupal\Core\ImageToolkit;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;

/**
 * Provides a base class for image toolkit operation plugins.
 *
 * @see \Drupal\Core\ImageToolkit\Annotation\ImageToolkitOperation
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationInterface
 * @see \Drupal\Core\ImageToolkit\ImageToolkitOperationManager
 * @see plugin_api
 */
abstract class ImageToolkitOperationBase extends PluginBase implements ImageToolkitOperationInterface {

  /**
   * The image toolkit.
   *
   * @var \Drupal\Core\ImageToolkit\ImageToolkitInterface
   */
  protected $toolkit;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an image toolkit operation plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitInterface $toolkit
   *   The image toolkit.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitInterface $toolkit, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->toolkit = $toolkit;
    $this->logger = $logger;
  }

  /**
   * Returns the image toolkit instance for this operation.
   *
   * Image toolkit implementers should provide a toolkit operation base class
   * that overrides this method to correctly document the return type of this
   * getter. This provides better DX (code checking and code completion) for
   * image toolkit operation developers.
   *
   * @return \Drupal\Core\ImageToolkit\ImageToolkitInterface
   *   The image toolkit in use.
   */
  protected function getToolkit() {
    return $this->toolkit;
  }

  /**
   * Returns the definition of the operation arguments.
   *
   * Image toolkit operation implementers must implement this method to
   * "document" their operation, thus also if no arguments are expected.
   *
   * @return array
   *   An array whose keys are the names of the arguments (e.g. "width",
   *   "degrees") and each value is an associative array having the following
   *   keys:
   *   - description: A string with the argument description. This is used only
   *     internally for documentation purposes, so it does not need to be
   *     translatable.
   *   - required: (optional) A boolean indicating if this argument should be
   *     provided or not. Defaults to TRUE.
   *   - default: (optional) When the argument is set to "required" = FALSE,
   *     this must be set to a default value. Ignored for "required" = TRUE
   *     arguments.
   */
  abstract protected function arguments();

  /**
   * Checks for required arguments and adds optional argument defaults.
   *
   * Image toolkit operation implementers should not normally need to override
   * this method as they should place their own validation in validateArguments.
   *
   * @param array $arguments
   *   An associative array of arguments to be used by the toolkit operation.
   *
   * @return array
   *   The prepared arguments array.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function prepareArguments(array $arguments) {
    foreach ($this->arguments() as $id => $argument) {
      $argument += ['required' => TRUE];
      // Check if the argument is required and, if so, has been provided.
      if ($argument['required']) {
        if (!array_key_exists($id, $arguments)) {
          // If the argument is required throw an exception.
          throw new \InvalidArgumentException("Argument '$id' expected by plugin '{$this->getPluginId()}' but not passed");
        }
      }
      else {
        // Optional arguments require a 'default' value.
        // We check this even if the argument is provided by the caller, as we
        // want to fail fast here, i.e. at development time.
        if (!array_key_exists('default', $argument)) {
          // The plugin did not define a default, so throw a plugin exception,
          // not an invalid argument exception.
          throw new InvalidPluginDefinitionException("Default for argument '$id' expected by plugin '{$this->getPluginId()}' but not defined");
        }

        // Use the default value if the argument is not passed in.
        if (!array_key_exists($id, $arguments)) {
          $arguments[$id] = $argument['default'];
        }
      }
    }
    return $arguments;
  }

  /**
   * Validates the arguments.
   *
   * Image toolkit operation implementers should place any argument validation
   * in this method, throwing an InvalidArgumentException when an error is
   * encountered.
   *
   * Validation typically includes things like:
   * - Checking that width and height are not negative.
   * - Checking that a color value is indeed a color.
   *
   * But validation may also include correcting the arguments, e.g:
   * - Casting arguments to the correct type.
   * - Rounding pixel values to an integer.
   *
   * This base implementation just returns the array of arguments and thus does
   * not need to be called by overriding methods.
   *
   * @param array $arguments
   *   An associative array of arguments to be used by the toolkit operation.
   *
   * @return array
   *   The validated and corrected arguments array.
   *
   * @throws \InvalidArgumentException
   *   If one or more of the arguments are not valid.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If the plugin does not define a default for an optional argument.
   */
  protected function validateArguments(array $arguments) {
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  final public function apply(array $arguments) {
    $arguments = $this->prepareArguments($arguments);
    $arguments = $this->validateArguments($arguments);
    return $this->execute($arguments);
  }

  /**
   * Performs the actual manipulation on the image.
   *
   * Image toolkit operation implementers must implement this method. This
   * method is responsible for actually performing the operation on the image.
   * When this method gets called, the implementer may assume all arguments,
   * also the optional ones, to be available, validated and corrected.
   *
   * @param array $arguments
   *   An associative array of arguments to be used by the toolkit operation.
   *
   * @return bool
   *   TRUE if the operation was performed successfully, FALSE otherwise.
   *
   * @throws \RuntimeException
   *   If the operation can not be performed.
   */
  abstract protected function execute(array $arguments);

}
