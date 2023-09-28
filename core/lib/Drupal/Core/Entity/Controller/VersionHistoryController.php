<?php

namespace Drupal\Core\Entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller showing revision history for an entity.
 *
 * This controller is agnostic to any entity type by using
 * \Drupal\Core\Entity\RevisionLogInterface.
 */
class VersionHistoryController extends ControllerBase {

  const REVISIONS_PER_PAGE = 50;

  /**
   * Constructs a new VersionHistoryController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    protected DateFormatterInterface $dateFormatter,
    protected RendererInterface $renderer,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('date.formatter'),
      $container->get('renderer'),
    );
  }

  /**
   * Generates an overview table of revisions for an entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return array
   *   A render array.
   */
  public function __invoke(RouteMatchInterface $routeMatch): array {
    $entityTypeId = $routeMatch->getRouteObject()->getOption('entity_type_id');
    $entity = $routeMatch->getParameter($entityTypeId);
    return $this->revisionOverview($entity);
  }

  /**
   * Builds a link to revert an entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity to build a revert revision link for.
   *
   * @return array|null
   *   A link to revert an entity revision, or NULL if the entity type does not
   *   have an a route to revert an entity revision.
   */
  protected function buildRevertRevisionLink(RevisionableInterface $revision): ?array {
    if (!$revision->hasLinkTemplate('revision-revert-form')) {
      return NULL;
    }

    $url = $revision->toUrl('revision-revert-form');
    // @todo Merge in cacheability after
    // https://www.drupal.org/project/drupal/issues/2473873.
    if (!$url->access()) {
      return NULL;
    }

    return [
      'title' => $this->t('Revert'),
      'url' => $url,
    ];
  }

  /**
   * Builds a link to delete an entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity to build a delete revision link for.
   *
   * @return array|null
   *   A link render array.
   */
  protected function buildDeleteRevisionLink(RevisionableInterface $revision): ?array {
    if (!$revision->hasLinkTemplate('revision-delete-form')) {
      return NULL;
    }

    $url = $revision->toUrl('revision-delete-form');
    // @todo Merge in cacheability after
    // https://www.drupal.org/project/drupal/issues/2473873.
    if (!$url->access()) {
      return NULL;
    }

    return [
      'title' => $this->t('Delete'),
      'url' => $url,
    ];
  }

  /**
   * Get a description of the revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity revision.
   *
   * @return array
   *   A render array describing the revision.
   */
  protected function getRevisionDescription(RevisionableInterface $revision): array {
    $context = [];
    if ($revision instanceof RevisionLogInterface) {
      // Use revision link to link to revisions that are not active.
      ['type' => $dateFormatType, 'format' => $dateFormatFormat] = $this->getRevisionDescriptionDateFormat($revision);
      $linkText = $this->dateFormatter->format($revision->getRevisionCreationTime(), $dateFormatType, $dateFormatFormat);

      // @todo Simplify this when https://www.drupal.org/node/2334319 lands.
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];
      $context['username'] = $this->renderer->render($username);
    }
    else {
      $linkText = $revision->access('view label') ? $revision->label() : $this->t('- Restricted access -');
    }

    $url = $revision->hasLinkTemplate('revision') ? $revision->toUrl('revision') : NULL;
    $context['revision'] = $url && $url->access()
      ? Link::fromTextAndUrl($linkText, $url)->toString()
      : (string) $linkText;
    $context['message'] = $revision instanceof RevisionLogInterface ? [
      '#markup' => $revision->getRevisionLogMessage(),
      '#allowed_tags' => Xss::getHtmlTagList(),
    ] : '';

    return [
      'data' => [
        '#type' => 'inline_template',
        '#template' => isset($context['username'])
          ? '{% trans %} {{ revision }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}'
          : '{% trans %} {{ revision }} {% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
        '#context' => $context,
      ],
    ];
  }

  /**
   * Date format to use for revision description dates.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The revision in context.
   *
   * @return array
   *   An array with keys 'type' and optionally 'format' suitable for passing
   *   to date formatter service.
   */
  protected function getRevisionDescriptionDateFormat(RevisionableInterface $revision): array {
    return [
      'type' => 'short',
      'format' => '',
    ];
  }

  /**
   * Generates revisions of an entity relevant to the current language.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The entity.
   *
   * @return \Generator|\Drupal\Core\Entity\RevisionableInterface
   *   Generates revisions.
   */
  protected function loadRevisions(RevisionableInterface $entity) {
    $entityType = $entity->getEntityType();
    $translatable = $entityType->isTranslatable();
    $entityStorage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    assert($entityStorage instanceof RevisionableStorageInterface);

    $result = $entityStorage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition($entityType->getKey('id'), $entity->id())
      ->sort($entityType->getKey('revision'), 'DESC')
      ->pager(self::REVISIONS_PER_PAGE)
      ->execute();

    $currentLangcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    foreach ($entityStorage->loadMultipleRevisions(array_keys($result)) as $revision) {
      // Only show revisions that are affected by the language that is being
      // displayed.
      if (!$translatable || ($revision->hasTranslation($currentLangcode) && $revision->getTranslation($currentLangcode)->isRevisionTranslationAffected())) {
        yield ($translatable ? $revision->getTranslation($currentLangcode) : $revision);
      }
    }
  }

  /**
   * Generates an overview table of revisions of an entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   A revisionable entity.
   *
   * @return array
   *   A render array.
   */
  protected function revisionOverview(RevisionableInterface $entity): array {
    $build['entity_revisions_table'] = [
      '#theme' => 'table',
      '#header' => [
        'revision' => ['data' => $this->t('Revision')],
        'operations' => ['data' => $this->t('Operations')],
      ],
    ];

    foreach ($this->loadRevisions($entity) as $revision) {
      $build['entity_revisions_table']['#rows'][$revision->getRevisionId()] = $this->buildRow($revision);
    }

    $build['pager'] = ['#type' => 'pager'];

    (new CacheableMetadata())
      // Only dealing with this entity and no external dependencies.
      ->addCacheableDependency($entity)
      ->addCacheContexts(['languages:language_content'])
      ->applyTo($build);

    return $build;
  }

  /**
   * Builds a table row for a revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   An entity revision.
   *
   * @return array
   *   A table row.
   */
  protected function buildRow(RevisionableInterface $revision): array {
    $row = [];
    $rowAttributes = [];

    $row['revision']['data'] = $this->getRevisionDescription($revision);
    $row['operations']['data'] = [];

    // Revision status.
    if ($revision->isDefaultRevision()) {
      $rowAttributes['class'][] = 'revision-current';
      $row['operations']['data']['status']['#markup'] = $this->t('<em>Current revision</em>');
    }

    // Operation links.
    $links = $this->getOperationLinks($revision);
    if (count($links) > 0) {
      $row['operations']['data']['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    return ['data' => $row] + $rowAttributes;
  }

  /**
   * Get operations for an entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity to build revision links for.
   *
   * @return array
   *   An array of operation links.
   */
  protected function getOperationLinks(RevisionableInterface $revision): array {
    // Removes links which are inaccessible or not rendered.
    return array_filter([
      $this->buildRevertRevisionLink($revision),
      $this->buildDeleteRevisionLink($revision),
    ]);
  }

}
