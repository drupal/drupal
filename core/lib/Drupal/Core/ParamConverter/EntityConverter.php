<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
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
 * If you want to have the entity type itself dynamic in the URL you can
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
 * The conversion can be limited to certain entity bundles by specifying a
 * parameter 'bundle' definition property as an array:
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: entity:node
 *         bundle:
 *           - article
 *           - news
 * @endcode
 * In the above example, only node entities of types 'article' and 'news' are
 * converted. For a node of a different type, such as 'page', the route will
 * return 404 'Not found'.
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

    if (
      !empty($definition['bundle']) &&
      $entity instanceof EntityInterface &&
      !in_array($entity->bundle(), $definition['bundle'], TRUE)
    ) {
      return NULL;
    }

    return $entity;
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
