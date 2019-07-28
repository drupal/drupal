<?php

namespace Drupal\layout_builder;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines an interface for Section Storage type plugins.
 */
interface SectionStorageInterface extends SectionListInterface, PluginInspectionInterface, ContextAwarePluginInterface, AccessibleInterface {

  /**
   * Returns an identifier for this storage.
   *
   * @return string
   *   The unique identifier for this storage.
   */
  public function getStorageId();

  /**
   * Returns the type of this storage.
   *
   * Used in conjunction with the storage ID.
   *
   * @return string
   *   The type of storage.
   */
  public function getStorageType();

  /**
   * Derives the section list from the storage ID.
   *
   * @param string $id
   *   The storage ID, see ::getStorageId().
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the ID is invalid.
   *
   * @internal
   *   This should only be called during section storage instantiation.
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. The
   *   section list should be derived from context. See
   *   https://www.drupal.org/node/3016262.
   */
  public function getSectionListFromId($id);

  /**
   * Provides the routes needed for Layout Builder UI.
   *
   * Allows the plugin to add or alter routes during the route building process.
   * \Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait is provided for the
   * typical use case of building a standard Layout Builder UI.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   *
   * @see \Drupal\Core\Routing\RoutingEvents::ALTER
   */
  public function buildRoutes(RouteCollection $collection);

  /**
   * Gets the URL used when redirecting away from the Layout Builder UI.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getRedirectUrl();

  /**
   * Gets the URL used to display the Layout Builder UI.
   *
   * @param string $rel
   *   (optional) The link relationship type, for example: 'view' or 'disable'.
   *   Defaults to 'view'.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getLayoutBuilderUrl($rel = 'view');

  /**
   * Configures the plugin based on route values.
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string|null
   *   The section storage ID if it could be extracted, NULL otherwise.
   *
   * @internal
   *   This should only be called during section storage instantiation.
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0.
   *   \Drupal\layout_builder\SectionStorageInterface::deriveContextsFromRoute()
   *   should be used instead. See https://www.drupal.org/node/3016262.
   */
  public function extractIdFromRoute($value, $definition, $name, array $defaults);

  /**
   * Derives the available plugin contexts from route values.
   *
   * This should only be called during section storage instantiation,
   * specifically for use by the routing system. For all non-routing usages, use
   * \Drupal\Component\Plugin\ContextAwarePluginInterface::getContextValue().
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The available plugin contexts.
   *
   * @see \Drupal\Core\ParamConverter\ParamConverterInterface::convert()
   */
  public function deriveContextsFromRoute($value, $definition, $name, array $defaults);

  /**
   * Gets contexts for use during preview.
   *
   * When not in preview, ::getContexts() will be used.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The plugin contexts suitable for previewing.
   */
  public function getContextsDuringPreview();

  /**
   * Gets the label for the object using the sections.
   *
   * @return string
   *   The label, or NULL if there is no label defined.
   */
  public function label();

  /**
   * Saves the sections.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   */
  public function save();

  /**
   * Determines if this section storage is applicable for the current contexts.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   Refinable cacheability object, typically provided by the section storage
   *   manager. When implementing this method, populate $cacheability with any
   *   information that affects whether this storage is applicable.
   *
   * @return bool
   *   TRUE if this section storage is applicable, FALSE otherwise.
   *
   * @internal
   *   This method is intended to be called by
   *   \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::findByContext().
   *
   * @see \Drupal\Core\Cache\RefinableCacheableDependencyInterface
   */
  public function isApplicable(RefinableCacheableDependencyInterface $cacheability);

  /**
   * Overrides \Drupal\Component\Plugin\PluginInspectionInterface::getPluginDefinition().
   *
   * @return \Drupal\layout_builder\SectionStorage\SectionStorageDefinition
   *   The section storage definition.
   */
  public function getPluginDefinition();

  /**
   * Overrides \Drupal\Core\Access\AccessibleInterface::access().
   *
   * @ingroup layout_builder_access
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE);

}
