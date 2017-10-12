<?php

namespace Drupal\Component\Plugin\Mapper;

/**
 * Plugin mapper interface.
 *
 * Plugin mappers are responsible for mapping a plugin request to its
 * implementation. For example, it might map a cache bin to a memcache bin.
 *
 * Mapper objects incorporate the best practices of retrieving configurations,
 * type information, and factory instantiation.
 */
interface MapperInterface {

  /**
   * Gets a preconfigured instance of a plugin.
   *
   * @param array $options
   *   An array of options that can be used to determine a suitable plugin to
   *   instantiate and how to configure it.
   *
   * @return object|false
   *   A fully configured plugin instance. The interface of the plugin instance
   *   will depend on the plugin type. If no instance can be retrieved, FALSE
   *   will be returned.
   */
  public function getInstance(array $options);

}
