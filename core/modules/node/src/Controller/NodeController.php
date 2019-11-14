<?php

namespace Drupal\node\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeStorageInterface;
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
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    if (!$entity_repository) {
      @trigger_error('The entity.repository service must be passed to NodeController::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'node_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('node_type')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use node types the user has access to.
    foreach ($this->entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', ['node_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Define
   *   entity form routes through the _entity_form instead through the
   *   _controller directive.
   */
  public function add(NodeTypeInterface $node_type) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Define entity form routes through the _entity_form instead through the _controller directive. See https://www.drupal.org/node/3084856', E_USER_DEPRECATED);
    $node = $this->entityTypeManager()->getStorage('node')->create([
      'type' => $node_type->id(),
    ]);

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
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionShow($node_revision) {
    $node = $this->entityTypeManager()->getStorage('node')->loadRevision($node_revision);
    $node = $this->entityRepository->getTranslationFromContext($node);
    $node_view_controller = new NodeViewController($this->entityTypeManager(), $this->renderer, $this->currentUser(), $this->entityRepository);
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
    $node = $this->entityTypeManager()->getStorage('node')->loadRevision($node_revision);
    return $this->t('Revision of %title from %date', ['%title' => $node->label(), '%date' => $this->dateFormatter->format($node->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(NodeInterface $node) {
    $account = $this->currentUser();
    $langcode = $node->language()->getId();
    $langname = $node->language()->getName();
    $languages = $node->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $type = $node->getType();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $node->label()]) : $this->t('Revisions for %title', ['%title' => $node->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer nodes')) && $node->access('update'));
    $delete_permission = (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer nodes')) && $node->access('delete'));

    $rows = [];
    $default_revision = $node->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($node, $node_storage) as $vid) {
      /** @var \Drupal\node\NodeInterface $revision */
      $revision = $node_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid]))->toString();
        }
        else {
          $link = $node->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $vid < $node->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $has_translations ?
                Url::fromRoute('node.revision_revert_translation_confirm', ['node' => $node->id(), 'node_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
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

          $rows[] = $row;
        }
      }
    }

    $build['node_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['node/drupal.node.admin'],
      ],
      '#attributes' => ['class' => 'node-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

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
    return $this->t('Create @name', ['@name' => $node_type->label()]);
  }

  /**
   * Gets a list of node revision IDs for a specific node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage handler.
   *
   * @return int[]
   *   Node revision IDs (in descending order).
   */
  protected function getRevisionIds(NodeInterface $node, NodeStorageInterface $node_storage) {
    $result = $node_storage->getQuery()
      ->allRevisions()
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->sort($node->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
