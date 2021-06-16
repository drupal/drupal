<?php

namespace Drupal\image_test\Plugin\ImageToolkit;

/**
 * Provides a derivative of TestToolkit.
 *
 * @ImageToolkit(
 *   id = "test:derived_toolkit",
 *   title = @Translation("A dummy toolkit, derivative of 'test'.")
 * )
 */
class DerivedToolkit extends TestToolkit {}
