<?php

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

/**
 * Builds an image toolkit operation.
 *
 * @ImageToolkitOperation(
 *   id = "bar",
 *   toolkit = "test",
 *   operation = "invert",
 *   label = @Translation("Invert"),
 *   description = @Translation("Bar.")
 * )
 */
class Bar extends OperationBase {}
