<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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

  /**
   * Entity manager which performs the upcasting in the end.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface|null $language_manager
   *   (optional) The language manager. Defaults to none.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager = NULL) {
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    $storage = $this->entityManager->getStorage($entity_type_id);
    $entity_definition = $this->entityManager->getDefinition($entity_type_id);

    $entity = $storage->load($value);

    // If the entity type is revisionable and the parameter has the
    // "load_latest_revision" flag, load the latest revision.
    if ($entity instanceof RevisionableInterface && !empty($definition['load_latest_revision']) && $entity_definition->isRevisionable()) {
      // Retrieve the latest revision ID taking translations into account.
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
      $entity = $this->getLatestTranslationAffectedRevision($entity, $langcode);
    }

    // If the entity type is translatable, ensure we return the proper
    // translation object for the current context.
    if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
      $entity = $this->entityManager->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
    }

    return $entity;
  }

  /**
   * Returns the ID of the latest revision translation of the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $langcode
   *   The language of the revision translation to be loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface
   *   The latest translation-affecting revision for the specified entity, or
   *   just the latest revision, if the specified entity is not translatable or
   *   does not have a matching translation yet.
   */
  protected function getLatestTranslationAffectedRevision(RevisionableInterface $entity, $langcode) {
    $revision = NULL;
    $storage = $this->entityManager->getStorage($entity->getEntityTypeId());

    if ($entity instanceof TranslatableRevisionableInterface && $entity->isTranslatable()) {
      /** @var \Drupal\Core\Entity\TranslatableRevisionableStorageInterface $storage */
      $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);

      // If the latest translation-affecting revision was a default revision, it
      // is fine to load the latest revision instead, because in this case the
      // latest revision, regardless of it being default or pending, will always
      // contain the most up-to-date values for the specified translation. This
      // provides a BC behavior when the route is defined by a module always
      // expecting the latest revision to be loaded and to be the default
      // revision. In this particular case the latest revision is always going
      // to be the default revision, since pending revisions would not be
      // supported.
      /** @var \Drupal\Core\Entity\TranslatableRevisionableInterface $revision */
      $revision = $revision_id ? $this->loadRevision($entity, $revision_id) : NULL;
      if (!$revision || ($revision->wasDefaultRevision() && !$revision->isDefaultRevision())) {
        $revision = NULL;
      }
    }

    // Fall back to the latest revisions if no affected revision for the current
    // content language could be found. This is acceptable as it means the
    // entity is not translated. This is the correct logic also on monolingual
    // sites.
    if (!isset($revision)) {
      $revision_id = $storage->getLatestRevisionId($entity->id());
      $revision = $this->loadRevision($entity, $revision_id);
    }

    return $revision;
  }

  /**
   * Loads the specified entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $revision_id
   *   The identifier of the revision to be loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface
   *   An entity revision object.
   */
  protected function loadRevision(RevisionableInterface $entity, $revision_id) {
    // We explicitly perform a loose equality check, since a revision ID may
    // be returned as an integer or a string.
    if ($entity->getLoadedRevisionId() != $revision_id) {
      $storage = $this->entityManager->getStorage($entity->getEntityTypeId());
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
      return $this->entityManager->hasDefinition($entity_type_id);
    }
    return FALSE;
  }

  /**
   * Determines the entity type ID given a route definition and route defaults.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   Thrown when the dynamic entity type is not found in the route defaults.
   */
  protected function getEntityTypeFromDefaults($definition, $name, array $defaults) {
    $entity_type_id = substr($definition['type'], strlen('entity:'));

    // If the entity type is dynamic, it will be pulled from the route defaults.
    if (strpos($entity_type_id, '{') === 0) {
      $entity_type_slug = substr($entity_type_id, 1, -1);
      if (!isset($defaults[$entity_type_slug])) {
        throw new ParamNotConvertedException(sprintf('The "%s" parameter was not converted because the "%s" parameter is missing', $name, $entity_type_slug));
      }
      $entity_type_id = $defaults[$entity_type_slug];
    }
    return $entity_type_id;
  }

  /**
   * Returns a language manager instance.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   *
   * @internal
   */
  protected function languageManager() {
    if (!isset($this->languageManager)) {
      $this->languageManager = \Drupal::languageManager();
      // @todo Turn this into a proper error (E_USER_ERROR) in
      //   https://www.drupal.org/node/2938929.
      @trigger_error('The language manager parameter has been added to EntityConverter since version 8.5.0 and will be made required in version 9.0.0 when requesting the latest translation-affected revision of an entity.', E_USER_DEPRECATED);
    }
    return $this->languageManager;
  }

}
