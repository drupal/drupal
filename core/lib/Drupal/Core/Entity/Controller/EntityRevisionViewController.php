<?php

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to view an entity revision.
 */
class EntityRevisionViewController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Creates a new EntityRevisionViewController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
    protected DateFormatterInterface $dateFormatter,
    TranslationInterface $translation,
  ) {
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('date.formatter'),
      $container->get('string_translation'),
    );
  }

  /**
   * Provides a page to render a single entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $_entity_revision
   *   The Entity to be rendered. Note this variable is named $_entity_revision
   *   rather than $entity to prevent collisions with other named placeholders
   *   in the route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array.
   */
  public function __invoke(RevisionableInterface $_entity_revision, string $view_mode = 'full'): array {
    $entityTypeId = $_entity_revision->getEntityTypeId();

    $page = $this->entityTypeManager
      ->getViewBuilder($entityTypeId)
      ->view($_entity_revision, $view_mode);

    $page['#entity_type'] = $entityTypeId;
    $page['#' . $entityTypeId] = $_entity_revision;
    return $page;
  }

  /**
   * Provides a title callback for a revision of an entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $_entity_revision
   *   The revisionable entity, passed in directly from request attributes.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title for the entity revision view page.
   */
  public function title(RevisionableInterface $_entity_revision): TranslatableMarkup {
    $revision = $this->entityRepository->getTranslationFromContext($_entity_revision);
    $titleArgs = ['%title' => $revision->label()];
    if (!$revision instanceof RevisionLogInterface) {
      return $this->t('Revision of %title', $titleArgs);
    }

    $titleArgs['%date'] = $this->dateFormatter->format($revision->getRevisionCreationTime());
    return $this->t('Revision of %title from %date', $titleArgs);
  }

}
