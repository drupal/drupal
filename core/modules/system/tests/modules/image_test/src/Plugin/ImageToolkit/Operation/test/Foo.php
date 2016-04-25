<?php

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

/**
 * Builds an image toolkit operation.
 *
 * @ImageToolkitOperation(
 *   id = "foo",
 *   toolkit = "test",
 *   operation = "blur",
 *   label = @Translation("Blur"),
 *   description = @Translation("Foo.")
 * )
 */
class Foo extends OperationBase { }
