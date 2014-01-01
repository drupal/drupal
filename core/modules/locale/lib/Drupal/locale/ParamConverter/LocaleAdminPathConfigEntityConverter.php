<?php

/**
 * @file
 * Contains \Drupal\locale\ParamConverter\LocaleAdminPathConfigEntityConverter.
 */

namespace Drupal\locale\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Makes sure the untranslated ConfigEntity is loaded on admin pages.
 *
 * Converts entity route arguments to untranslated entities (in their original
 * submission language) as opposed to converting to entities with overrides in
 * the negotiated language.
 *
 * This converter applies only if the path is an admin path.
 *
 * Due to this converter having a higher weight than the default
 * EntityConverter, every time this applies, it takes over the conversion duty
 * from EntityConverter. As we only allow a single converter per route
 * argument, EntityConverter is ignored when this converter applies.
 */
class LocaleAdminPathConfigEntityConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    $entity_type = substr($definition['type'], strlen('entity:'));
    if ($storage = $this->entityManager->getStorageController($entity_type)) {
      // Enter the override-free context, so we can ensure no overrides are
      // applied.
      config_context_enter('config.context.free');
      $entity = $storage->load($value);
      // Leave the override-free context.
      config_context_leave();
      return $entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (parent::applies($definition, $name, $route)) {
      // As we only want to override EntityConverter for ConfigEntities, find
      // out whether the current entity is a ConfigEntity.
      $entity_type = substr($definition['type'], strlen('entity:'));
      $info = $this->entityManager->getDefinition($entity_type);
      if ($info->isSubclassOf('\Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        // path_is_admin() needs the path without the leading slash.
        $path = ltrim($route->getPath(), '/');
        return path_is_admin($path);
      }
    }
    return FALSE;
  }

}
