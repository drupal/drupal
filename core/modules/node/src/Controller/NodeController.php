<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeController.
 */

namespace Drupal\node\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\Date;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Node routes.
 */
class NodeController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $date;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Datetime\Date $date
   *   The date service.
   */
  public function __construct(Connection $database, Date $date) {
    $this->date = $date;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'), $container->get('date'));
  }


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
    foreach ($this->entityManager()->getStorage('node_type')->loadMultiple() as $type) {
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

    $node = $this->entityManager()->getStorage('node')->create(array(
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
    $node = $this->entityManager()->getStorage('node')->loadRevision($node_revision);
    $node_view_controller = new NodeViewController($this->entityManager);
    $page = $node_view_controller->view($node);
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
    $node = $this->entityManager()->getStorage('node')->loadRevision($node_revision);
    return $this->t('Revision of %title from %date', array('%title' => $node->label(), '%date' => format_date($node->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(NodeInterface $node) {
    $account = $this->currentUser();
    $node_storage = $this->entityManager()->getStorage('node');
    $type = $node->getType();

    $build = array();
    $build['#title'] = $this->t('Revisions for %title', array('%title' => $node->label()));
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer nodes')) && $node->access('update'));
    $delete_permission =  (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer nodes')) && $node->access('delete'));

    $rows = array();

    $vids = $node_storage->revisionIds($node);

    foreach (array_reverse($vids) as $vid) {
      if ($revision = $node_storage->loadRevision($vid)) {
        $row = array();

        $revision_author = $revision->uid->entity;

        if ($vid == $node->getRevisionId()) {
          $username = array(
            '#theme' => 'username',
            '#account' => $revision_author,
          );
          $row[] = array('data' => $this->t('!date by !username', array('!date' => $this->l($this->date->format($revision->revision_timestamp->value, 'short'), 'node.view', array('node' => $node->id())), '!username' => drupal_render($username)))
            . (($revision->revision_log->value != '') ? '<p class="revision-log">' . Xss::filter($revision->revision_log->value) . '</p>' : ''),
            'class' => array('revision-current'));
          $row[] = array('data' => String::placeholder($this->t('current revision')), 'class' => array('revision-current'));
        }
        else {
          $username = array(
            '#theme' => 'username',
            '#account' => $revision_author,
          );
          $row[] = $this->t('!date by !username', array('!date' => $this->l($this->date->format($revision->revision_timestamp->value, 'short'), 'node.revision_show', array('node' => $node->id(), 'node_revision' => $vid)), '!username' => drupal_render($username)))
            . (($revision->revision_log->value != '') ? '<p class="revision-log">' . Xss::filter($revision->revision_log->value) . '</p>' : '');

          if ($revert_permission) {
            $links['revert'] = array(
              'title' => $this->t('Revert'),
              'route_name' => 'node.revision_revert_confirm',
              'route_parameters' => array('node' => $node->id(), 'node_revision' => $vid),
            );
          }

          if ($delete_permission) {
            $links['delete'] = array(
              'title' => $this->t('Delete'),
              'route_name' => 'node.revision_delete_confirm',
              'route_parameters' => array('node' => $node->id(), 'node_revision' => $vid),
            );
          }

          $row[] = array(
            'data' => array(
              '#type' => 'operations',
              '#links' => $links,
            ),
          );
        }

        $rows[] = $row;
      }
    }

    $build['node_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'library' => array('node/drupal.node.admin'),
      ),
    );

    return $build;
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
