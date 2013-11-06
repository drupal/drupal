<?php

/**
 * @file
 * Definition of Drupal\node\NodeViewBuilder.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for nodes.
 */
class NodeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    $return = array();
    if (empty($entities)) {
      return $return;
    }

    // Attach user account.
    user_attach_accounts($entities);

    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      $entity->content['links'] = array(
        '#theme' => 'links__node',
        '#pre_render' => array('drupal_pre_render_links'),
        '#attributes' => array('class' => array('links', 'inline')),
      );

      // Always display a read more link on teasers because we have no way
      // to know when a teaser view is different than a full view.
      $links = array();
      if ($view_mode == 'teaser') {
        $node_title_stripped = strip_tags($entity->label());
        $links['node-readmore'] = array(
          'title' => t('Read more<span class="visually-hidden"> about @title</span>', array(
            '@title' => $node_title_stripped,
          )),
          'href' => 'node/' . $entity->id(),
          'html' => TRUE,
          'attributes' => array(
            'rel' => 'tag',
            'title' => $node_title_stripped,
          ),
        );
      }

      $entity->content['links']['node'] = array(
        '#theme' => 'links__node__node',
        '#links' => $links,
        '#attributes' => array('class' => array('links', 'inline')),
      );

      // Add Language field text element to node render array.
      if ($display->getComponent('langcode')) {
        $entity->content['langcode'] = array(
          '#type' => 'item',
          '#title' => t('Language'),
          '#markup' => language_name($langcode),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>'
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityDisplay $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    if ($entity->id()) {
      $build['#contextual_links']['node'] = array(
        'route_parameters' =>array('node' => $entity->id()),
      );
    }

    // The node 'submitted' info is not rendered in a standard way (renderable
    // array) so we have to add a cache tag manually.
    $build['#cache']['tags']['user'][] = $entity->getAuthorId();
  }

}
