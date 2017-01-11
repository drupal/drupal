<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Routing\AdminContext;
use Symfony\Component\Routing\Route;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Makes sure the unmodified ConfigEntity is loaded on admin pages.
 *
 * Converts entity route arguments to unmodified entities as opposed to
 * converting to entities with overrides, such as the negotiated language.
 *
 * This converter applies only if the path is an admin path, the entity is
 * a config entity, and the "with_config_overrides" element is not set to TRUE
 * on the parameter definition.
 *
 * Due to this converter having a higher weight than the default
 * EntityConverter, every time this applies, it takes over the conversion duty
 * from EntityConverter. As we only allow a single converter per route
 * argument, EntityConverter is ignored when this converter applies.
 */
class AdminPathConfigEntityConverter extends EntityConverter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, AdminContext $admin_context) {
    parent::__construct($entity_manager);

    $this->configFactory = $config_factory;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);

    // If the entity type is dynamic, confirm it to be a config entity. Static
    // entity types will have performed this check in self::applies().
    if (strpos($definition['type'], 'entity:{') === 0) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      if (!$entity_type->entityClassImplements(ConfigEntityInterface::class)) {
        return parent::convert($value, $definition, $name, $defaults);
      }
    }

    if ($storage = $this->entityManager->getStorage($entity_type_id)) {
      // Make sure no overrides are loaded.
      return $storage->loadOverrideFree($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (isset($definition['with_config_overrides']) && $definition['with_config_overrides']) {
      return FALSE;
    }

    if (parent::applies($definition, $name, $route)) {
      $entity_type_id = substr($definition['type'], strlen('entity:'));
      // If the entity type is dynamic, defer checking to self::convert().
      if (strpos($entity_type_id, '{') === 0) {
        return TRUE;
      }
      // As we only want to override EntityConverter for ConfigEntities, find
      // out whether the current entity is a ConfigEntity.
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      if ($entity_type->entityClassImplements(ConfigEntityInterface::class)) {
        return $this->adminContext->isAdminRoute($route);
      }
    }
    return FALSE;
  }

}
