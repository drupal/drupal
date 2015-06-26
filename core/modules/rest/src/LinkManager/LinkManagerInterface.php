<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\LinkManagerInterface.
 */

namespace Drupal\rest\LinkManager;

/**
 * Interface implemented by link managers.
 *
 * There are no explicit methods on the manager interface. Instead link managers
 * broker the interactions of the different components, and therefore must
 * implement each component interface, which is enforced by this interface
 * extending all of the component ones.
 *
 * While a link manager may directly implement these interface methods with
 * custom logic, it is expected to be more common for plugin managers to proxy
 * the method invocations to the respective components.
 */
interface LinkManagerInterface extends TypeLinkManagerInterface, RelationLinkManagerInterface {
}
