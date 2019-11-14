<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity IDs to full objects.
 *
 * This is useful in cases where the dynamic elements of the path can't be
 * auto-determined; for example, if your path refers to multiple of the same
 * type of entity ("example/{node1}/foo/{node2}") or if the path can act on any
 * entity type ("example/{entity_type}/{entity}/foo").
 *
 * In order to use it you should specify some additional options in your route:
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:node
 * @endcode
 *
 * If you want to have the entity type itself dynamic in the url you can
 * specify it like the following:
 * @code
 * example.route:
 *   path: foo/{entity_type}/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:{entity_type}
 * @endcode
 *
 * If your route needs to support pending revisions, you can specify the
 * "load_latest_revision" parameter. This will ensure that the latest revision
 * is returned, even if it is not the default one:
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:node
 *         load_latest_revision: TRUE
 * @endcode
 *
 * When dealing with translatable entities, the "load_latest_revision" flag will
 * make this converter load the latest revision affecting the translation
 * matching the content language for the current request. If none can be found
 * it will fall back to the latest revision. For instance, if an entity has an
 * English default revision (revision 1) and an Italian pending revision
 * (revision 2), "/foo/1" will return the former, while "/it/foo/1" will return
 * the latter.
 *
 * @see entities_revisions_translations
 */
class EntityConverter implements ParamConverterInterface {

  use DynamicEntityTypeParamConverterTrait;

  /**
   * Entity type manager which performs the upcasting in the end.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   *
   * @see https://www.drupal.org/node/2938929
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);

    // If the entity type is revisionable and the parameter has the
    // "load_latest_revision" flag, load the active variant.
    if (!empty($definition['load_latest_revision'])) {
      return $this->entityRepository->getActive($entity_type_id, $value);
    }

    // Do not inject the context repository as it is not an actual dependency:
    // it will be removed once both the TODOs below are fixed.
    /** @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contexts_repository */
    $contexts_repository = \Drupal::service('context.repository');
    // @todo Consider deprecating the legacy context operation altogether in
    //   https://www.drupal.org/node/3031124.
    $contexts = $contexts_repository->getAvailableContexts();
    $contexts[EntityRepositoryInterface::CONTEXT_ID_LEGACY_CONTEXT_OPERATION] =
      new Context(new ContextDefinition('string'), 'entity_upcast');
    // @todo At the moment we do not need the current user context, which is
    //   triggering some test failures. We can remove these lines once
    //   https://www.drupal.org/node/2934192 is fixed.
    $context_id = '@user.current_user_context:current_user';
    if (isset($contexts[$context_id])) {
      $account = $contexts[$context_id]->getContextValue();
      unset($account->_skipProtectedUserFieldConstraint);
      unset($contexts[$context_id]);
    }
    $entity = $this->entityRepository->getCanonical($entity_type_id, $value, $contexts);

    return $entity;
  }

  /**
   * Returns the latest revision translation of the specified entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $langcode
   *   The language of the revision translation to be loaded.
   *
   * @return \Drupal\Core\Entity\RevisionableInterface
   *   The latest translation-affecting revision for the specified entity, or
   *   just the latest revision, if the specified entity is not translatable or
   *   does not have a matching translation yet.
   *
   * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
   *   Use \Drupal\Core\Entity\EntityRepositoryInterface::getActive() instead.
   */
  protected function getLatestTranslationAffectedRevision(RevisionableInterface $entity, $langcode) {
    @trigger_error('\Drupal\Core\ParamConverter\EntityConverter::getLatestTranslationAffectedRevision() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Entity\EntityRepositoryInterface::getActive() instead.', E_USER_DEPRECATED);
    $data_type = 'language';
    $context_id_prefix = '@language.current_language_context:';
    $contexts = [
      $context_id_prefix . LanguageInterface::TYPE_CONTENT => new Context(new ContextDefinition($data_type), $langcode),
      $context_id_prefix . LanguageInterface::TYPE_INTERFACE => new Context(new ContextDefinition($data_type), $langcode),
    ];
    $revision = $this->entityRepository->getActive($entity->getEntityTypeId(), $entity->id(), $contexts);
    // The EntityRepositoryInterface::getActive() method performs entity
    // translation negotiation, but this used to return an untranslated entity
    // object as translation negotiation happened later in ::convert().
    if ($revision instanceof TranslatableInterface) {
      $revision = $revision->getUntranslated();
    }
    return $revision;
  }

  /**
   * Loads the specified entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $revision_id
   *   The identifier of the revision to be loaded.
   *
   * @return \Drupal\Core\Entity\RevisionableInterface
   *   An entity revision object.
   *
   * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
   */
  protected function loadRevision(RevisionableInterface $entity, $revision_id) {
    @trigger_error('\Drupal\Core\ParamConverter\EntityConverter::loadRevision() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0.', E_USER_DEPRECATED);
    // We explicitly perform a loose equality check, since a revision ID may
    // be returned as an integer or a string.
    if ($entity->getLoadedRevisionId() != $revision_id) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      return $storage->loadRevision($revision_id);
    }
    else {
      return $entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'entity:') === 0) {
      $entity_type_id = substr($definition['type'], strlen('entity:'));
      if (strpos($definition['type'], '{') !== FALSE) {
        $entity_type_slug = substr($entity_type_id, 1, -1);
        return $name != $entity_type_slug && in_array($entity_type_slug, $route->compile()->getVariables(), TRUE);
      }
      return $this->entityTypeManager->hasDefinition($entity_type_id);
    }
    return FALSE;
  }

}
