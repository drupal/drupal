<?php

namespace Drupal\content_moderation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alters renderable arrays on behalf of hook implementations.
 *
 * @internal
 */
class BuildAlter implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The Moderation Information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a new BuildAlter.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(ModerationInformationInterface $moderation_info, TranslationInterface $string_translation) {
    $this->moderationInfo = $moderation_info;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('string_translation')
    );
  }

  /**
   * Add a revision status column to the the revisions overview table.
   *
   * @param array $build
   *   The renderable array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The default revision of the entity.
   * @param array $context
   *   An associative array containing:
   *   - all_revisions: An array of all the revisions of the entity, keyed by
   *     the revision ID.
   *   - displayed_revisions: An array of the revisions of the entity that are
   *     shown in the build array, keyed by the revision ID and in the same
   *     order as the rows in the build array.
   *
   * @see hook_entity_revision_overview_alter()
   */
  public function entityRevisionOverviewAlter(array &$build, EntityInterface $entity, array $context) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }

    $displayed_revisions = $context['displayed_revisions'];

    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);

    // Add the revision status after the first column.
    array_splice($build['entity_revisions_table']['#header'], 1, 0, [$this->t('Moderation state')]);

    foreach ($build['entity_revisions_table']['#rows'] as $index => &$row) {
      // The table rows are keyed numerically, whereas the array of revisions is
      // keyed by the vid. So the only way to get the revision for a row is to
      // rely on the two arrays being in the same order.
      $row_revision = array_shift($displayed_revisions);
      $revision_state = $row_revision->moderation_state->value;

      // A table row either is an array of cells, or has a 'data' array which
      // is the array of cells.
      if (isset($row['data'])) {
        array_splice($row['data'], 1, 0, [$workflow->getTypePlugin()->getState($revision_state)->label()]);
      }
      else {
        array_splice($row, 1, 0, [$workflow->getTypePlugin()->getState($revision_state)->label()]);
      }
    }
  }

}
