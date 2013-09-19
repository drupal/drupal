<?php

/**
 * @file
 * Contains \Drupal\translation\Controller\TranslationController.
 */

namespace Drupal\translation\Controller;

use Drupal\node\NodeInterface;

/**
 * Controller routines for translation routes.
 */
class TranslationController {

  /**
   * @todo Remove translation_node_overview().
   */
  public function nodeOverview(NodeInterface $node) {
    module_load_include('pages.inc', 'translation');
    return translation_node_overview($node);
  }

}
