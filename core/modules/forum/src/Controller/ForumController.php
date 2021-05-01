<?php

namespace Drupal\forum\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for forum routes.
 */
class ForumController extends ControllerBase {

  /**
   * Forum manager service.
   *
   * @var \Drupal\forum\ForumManagerInterface
   */
  protected $forumManager;

  /**
   * Vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $nodeAccess;

  /**
   * Field map of existing fields on the site.
   *
   * @var array
   */
  protected $fieldMap;

  /**
   * Node type storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Node entity type, we need to get cache tags from here.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $nodeEntityTypeDefinition;

  /**
   * Comment entity type, we need to get cache tags from here.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $commentEntityTypeDefinition;

  /**
   * Constructs a ForumController object.
   *
   * @param \Drupal\forum\ForumManagerInterface $forum_manager
   *   The forum manager service.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   Vocabulary storage.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   Term storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Entity\EntityAccessControlHandlerInterface $node_access
   *   Node access control handler.
   * @param array $field_map
   *   Array of active fields on the site.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   Node type storage handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeInterface $node_entity_type_definition
   *   Node entity type definition object
   * @param \Drupal\Core\Entity\EntityTypeInterface $comment_entity_type_definition
   *   Comment entity type definition object
   */
  public function __construct(ForumManagerInterface $forum_manager, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, AccountInterface $current_user, EntityAccessControlHandlerInterface $node_access, array $field_map, EntityStorageInterface $node_type_storage, RendererInterface $renderer, EntityTypeInterface $node_entity_type_definition, EntityTypeInterface $comment_entity_type_definition) {
    $this->forumManager = $forum_manager;
    $this->vocabularyStorage = $vocabulary_storage;
    $this->termStorage = $term_storage;
    $this->currentUser = $current_user;
    $this->nodeAccess = $node_access;
    $this->fieldMap = $field_map;
    $this->nodeTypeStorage = $node_type_storage;
    $this->renderer = $renderer;
    $this->nodeEntityTypeDefinition = $node_entity_type_definition;
    $this->commentEntityTypeDefinition = $comment_entity_type_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('forum_manager'),
      $entity_type_manager->getStorage('taxonomy_vocabulary'),
      $entity_type_manager->getStorage('taxonomy_term'),
      $container->get('current_user'),
      $entity_type_manager->getAccessControlHandler('node'),
      $container->get('entity_field.manager')->getFieldMap(),
      $entity_type_manager->getStorage('node_type'),
      $container->get('renderer'),
      $entity_type_manager->getDefinition('node'),
      $entity_type_manager->getDefinition('comment')
    );
  }

  /**
   * Returns forum page for a given forum.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The forum to render the page for.
   *
   * @return array
   *   A render array.
   */
  public function forumPage(TermInterface $taxonomy_term) {
    // Get forum details.
    $taxonomy_term->forums = $this->forumManager->getChildren($this->config('forum.settings')->get('vocabulary'), $taxonomy_term->id());
    $taxonomy_term->parents = $this->termStorage->loadAllParents($taxonomy_term->id());

    if (empty($taxonomy_term->forum_container->value)) {
      $build = $this->forumManager->getTopics($taxonomy_term->id(), $this->currentUser());
      $topics = $build['topics'];
      $header = $build['header'];
    }
    else {
      $topics = [];
      $header = [];
    }
    return $this->build($taxonomy_term->forums, $taxonomy_term, $topics, $taxonomy_term->parents, $header);
  }

  /**
   * Returns forum index page.
   *
   * @return array
   *   A render array.
   */
  public function forumIndex() {
    $vocabulary = $this->vocabularyStorage->load($this->config('forum.settings')->get('vocabulary'));
    $index = $this->forumManager->getIndex();
    $build = $this->build($index->forums, $index);
    if (empty($index->forums)) {
      // Root of empty forum.
      $build['#title'] = $this->t('No forums defined');
    }
    else {
      // Set the page title to forum's vocabulary name.
      $build['#title'] = $vocabulary->label();
      $this->renderer->addCacheableDependency($build, $vocabulary);
    }
    return $build;
  }

  /**
   * Returns a renderable forum index page array.
   *
   * @param array $forums
   *   A list of forums.
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term of the forum.
   * @param array $topics
   *   The topics of this forum.
   * @param array $parents
   *   The parent forums in relation this forum.
   * @param array $header
   *   Array of header cells.
   *
   * @return array
   *   A render array.
   */
  protected function build($forums, TermInterface $term, $topics = [], $parents = [], $header = []) {
    $config = $this->config('forum.settings');
    $build = [
      '#theme' => 'forums',
      '#forums' => $forums,
      '#topics' => $topics,
      '#parents' => $parents,
      '#header' => $header,
      '#term' => $term,
      '#sortby' => $config->get('topics.order'),
      '#forums_per_page' => $config->get('topics.page_limit'),
    ];
    if (empty($term->forum_container->value)) {
      $build['#attached']['feed'][] = ['taxonomy/term/' . $term->id() . '/feed', 'RSS - ' . $term->getName()];
    }
    $this->renderer->addCacheableDependency($build, $config);

    foreach ($forums as $forum) {
      $this->renderer->addCacheableDependency($build, $forum);
    }
    foreach ($topics as $topic) {
      $this->renderer->addCacheableDependency($build, $topic);
    }
    foreach ($parents as $parent) {
      $this->renderer->addCacheableDependency($build, $parent);
    }
    $this->renderer->addCacheableDependency($build, $term);

    $is_forum = empty($term->forum_container->value);
    return [
      'action' => ($is_forum) ? $this->buildActionLinks($config->get('vocabulary'), $term) : [],
      'forum' => $build,
      '#cache' => [
        'tags' => Cache::mergeTags($this->nodeEntityTypeDefinition->getListCacheTags(), $this->commentEntityTypeDefinition->getListCacheTags()),
      ],
    ];
  }

  /**
   * Returns add forum entity form.
   *
   * @return array
   *   Render array for the add form.
   */
  public function addForum() {
    $vid = $this->config('forum.settings')->get('vocabulary');
    $taxonomy_term = $this->termStorage->create([
      'vid' => $vid,
      'forum_controller' => 0,
    ]);
    return $this->entityFormBuilder()->getForm($taxonomy_term, 'forum');
  }

  /**
   * Returns add container entity form.
   *
   * @return array
   *   Render array for the add form.
   */
  public function addContainer() {
    $vid = $this->config('forum.settings')->get('vocabulary');
    $taxonomy_term = $this->termStorage->create([
      'vid' => $vid,
      'forum_container' => 1,
    ]);
    return $this->entityFormBuilder()->getForm($taxonomy_term, 'container');
  }

  /**
   * Generates an action link to display at the top of the forum listing.
   *
   * @param string $vid
   *   Vocabulary ID.
   * @param \Drupal\taxonomy\TermInterface $forum_term
   *   The term for which the links are to be built.
   *
   * @return array
   *   Render array containing the links.
   */
  protected function buildActionLinks($vid, TermInterface $forum_term = NULL) {
    $user = $this->currentUser();

    $links = [];
    // Loop through all bundles for forum taxonomy vocabulary field.
    foreach ($this->fieldMap['node']['taxonomy_forums']['bundles'] as $type) {
      if ($this->nodeAccess->createAccess($type)) {
        $node_type = $this->nodeTypeStorage->load($type);
        $links[$type] = [
          '#theme' => 'menu_local_action',
          '#link' => [
            'title' => $this->t('Add new @node_type', [
              '@node_type' => $this->nodeTypeStorage->load($type)->label(),
            ]),
            'url' => Url::fromRoute('node.add', ['node_type' => $type]),
          ],
          '#cache' => [
            'tags' => $node_type->getCacheTags(),
          ],
        ];
        if ($forum_term && $forum_term->bundle() == $vid) {
          // We are viewing a forum term (specific forum), append the tid to
          // the url.
          $links[$type]['#link']['localized_options']['query']['forum_id'] = $forum_term->id();
        }
      }
    }
    if (empty($links)) {
      // Authenticated user does not have access to create new topics.
      if ($user->isAuthenticated()) {
        $links['disallowed'] = [
          '#markup' => $this->t('You are not allowed to post new content in the forum.'),
        ];
      }
      // Anonymous user does not have access to create new topics.
      else {
        $links['login'] = [
          '#theme' => 'menu_local_action',
          '#link' => [
            'title' => $this->t('Log in to post new content in the forum.'),
            'url' => Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]),
          ],
          // Without this workaround, the action links will be rendered as <li>
          // with no wrapping <ul> element.
          // @todo Find a better way for this in https://www.drupal.org/node/3181052.
          '#prefix' => '<ul class="action-links">',
          '#suffix' => '</ul>',
        ];
      }
    }
    else {
      // Without this workaround, the action links will be rendered as <li> with
      // no wrapping <ul> element.
      // @todo Find a better way for this in https://www.drupal.org/node/3181052.
      $links['#prefix'] = '<ul class="action-links">';
      $links['#suffix'] = '</ul>';
    }
    return $links;
  }

}
