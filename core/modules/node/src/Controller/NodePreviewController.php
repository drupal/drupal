<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodePreviewController.
 */

namespace Drupal\node\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single node in preview.
 */
class NodePreviewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node_preview, $view_mode_id = 'full', $langcode = NULL) {
    $node_preview->preview_view_mode = $view_mode_id;
    $build = array('nodes' => parent::view($node_preview, $view_mode_id));

    $build['#attached']['library'][] = 'node/drupal.node.preview';

    $build['#title'] = $build['nodes']['#title'];
    unset($build['nodes']['#title']);

    // Don't render cache previews.
    unset($build['nodes']['#cache']);

    foreach ($node_preview->uriRelationships() as $rel) {
      // Set the node path as the canonical URL to prevent duplicate content.
      $build['#attached']['drupal_add_html_head_link'][] = array(
        array(
        'rel' => $rel,
        'href' => $node_preview->url($rel),
        )
        , TRUE);

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['drupal_add_html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $node_preview->url($rel, array('alias' => TRUE)),
          )
        , TRUE);
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single node in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node_preview
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $node_preview) {
    return String::checkPlain($this->entityManager->getTranslationFromContext($node_preview)->label());
  }

}
