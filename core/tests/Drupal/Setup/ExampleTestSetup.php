<?php

namespace Drupal\Setup;

use Drupal\node\Entity\Node;

/**
 * Example test setup file.
 */
class ExampleTestSetup implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install(['node']);

    Node::create(['type' => 'page', 'title' => 'Test tile'])
      ->save();
  }

}
