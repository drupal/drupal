<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeController.
 */

namespace Drupal\node\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Node routes.
 */
class NodeController extends ControllerBase {

  /**
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @return array
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   redirects to the node add page for that one node type and does not return
   *   at all.
   *
   * @see node_menu()
   */
  public function addPage() {
    $content = array();

    // Only use node types the user has access to.
    foreach ($this->entityManager()->getStorageController('node_type')->loadMultiple() as $type) {
      if ($this->entityManager()->getAccessController('node')->createAccess($type->type)) {
        $content[$type->type] = $type;
      }
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', array('node_type' => $type->type));
    }

    return array(
      '#theme' => 'node_add_list',
      '#content' => $content,
    );
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function add(NodeTypeInterface $node_type) {
    $account = $this->currentUser();
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', array('node', $node_type->type));

    $node = $this->entityManager()->getStorageController('node')->create(array(
      'uid' => $account->id(),
      'name' => $account->getUsername() ?: '',
      'type' => $node_type->type,
      'langcode' => $langcode ? $langcode : $this->languageManager()->getCurrentLanguage()->id,
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Displays a node revision.
   *
   * @param int $node_revision
   *   The node revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($node_revision) {
    $node = $this->entityManager()->getStorageController('node')->loadRevision($node_revision);
    $page = $this->buildPage($node);
    unset($page['nodes'][$node->id()]['#cache']);

    return $page;
  }

  /**
   * Page title callback for a node revision.
   *
   * @param int $node_revision
   *   The node revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($node_revision) {
    $node = $this->entityManager()->getStorageController('node')->loadRevision($node_revision);
    return $this->t('Revision of %title from %date', array('%title' => $node->label(), '%date' => format_date($node->getRevisionCreationTime())));
  }

  /**
   * @todo Remove node_revision_overview().
   */
  public function revisionOverview(NodeInterface $node) {
    module_load_include('pages.inc', 'node');
    return node_revision_overview($node);
  }

  /**
   * Displays a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node we are displaying.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(NodeInterface $node) {
    $build = $this->buildPage($node);

    foreach ($node->uriRelationships() as $rel) {
      $uri = $node->urlInfo($rel);
      // Set the node path as the canonical URL to prevent duplicate content.
      $build['#attached']['drupal_add_html_head_link'][] = array(
        array(
        'rel' => $rel,
        'href' => $node->url($rel),
        )
        , TRUE);

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['drupal_add_html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $node->url($rel, array('alias' => TRUE)),
          )
        , TRUE);
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the node.view route.
   *
   * @param NodeInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(NodeInterface $node) {
    return String::checkPlain($this->entityManager()->getTranslationFromContext($node)->label());
  }

  /**
   * Builds a node page render array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node we are displaying.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  protected function buildPage(NodeInterface $node) {
    return array('nodes' => $this->entityManager()->getViewBuilder('node')->view($node));
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(NodeTypeInterface $node_type) {
    return $this->t('Create @name', array('@name' => $node_type->name));
  }

}
