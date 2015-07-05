<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeController.
 */

namespace Drupal\node\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Node routes.
 */
class NodeController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatter $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
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
      if ($this->entityManager()->getAccessControlHandler('node')->createAccess($type->id())) {
        $content[$type->id()] = $type;
      }
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', array('node_type' => $type->id()));
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
    $node = $this->entityManager()->getStorage('node')->create(array(
      'type' => $node_type->id(),
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
    $node_view_controller = new NodeViewController($this->entityManager, $this->renderer);
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
      $revision = $node_storage->loadRevision($vid);
      $username = [
        '#theme' => 'username',
        '#account' => $revision->uid->entity,
      ];

      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
      if ($vid != $node->getRevisionId()) {
        $link = $this->l($date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid]));
      }
      else {
        $link = $node->link($date);
      }

      $row = [];
      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => $this->renderer->renderPlain($username),
            'message' => Xss::filter($revision->revision_log->value),
          ],
        ],
      ];
      // @todo Simplify once https://www.drupal.org/node/2334319 lands.
      $this->renderer->addCacheableDependency($column['data'], $username);
      $row[] = $column;

      if ($vid == $node->getRevisionId()) {
        $row[0]['class'] = ['revision-current'];
        $row[] = [
          'data' => SafeMarkup::placeholder($this->t('current revision')),
          'class' => ['revision-current'],
        ];
      }
      else {
        $links = [];
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $this->t('Revert'),
            'url' => Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
          ];
        }

        if ($delete_permission) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('node.revision_delete_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
          ];
        }

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];
      }

      $rows[] = $row;
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
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(NodeTypeInterface $node_type) {
    return $this->t('Create @name', array('@name' => $node_type->label()));
  }

}
