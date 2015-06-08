<?php

/**
 * @file
 * Contains \Drupal\Core\Render\RenderCacheInterface.
 */

namespace Drupal\Core\Render;

/**
 * Defines an interface for caching rendered render arrays.
 *
 * @see sec_caching
 *
 * @see \Drupal\Core\Render\RendererInterface
 */
interface RenderCacheInterface {

  /**
   * Gets a cacheable render array for a render array and its rendered output.
   *
   * Given a render array and its rendered output (HTML string), return an array
   * data structure that allows the render array and its associated metadata to
   * be cached reliably (and is serialization-safe).
   *
   * If Drupal needs additional rendering metadata to be cached at some point,
   * consumers of this method will continue to work. Those who only cache
   * certain parts of a render array will cease to work.
   *
   * @param array $elements
   *   A render array, on which \Drupal\Core\Render\RendererInterface::render()
   *   has already been invoked.
   *
   * @return array
   *   An array representing the cacheable data for this render array.
   */
  public function getCacheableRenderArray(array $elements);

  /**
   * Gets the cached, pre-rendered element of a renderable element from cache.
   *
   * @param array $elements
   *   A renderable array.
   *
   * @return array|false
   *   A renderable array, with the original element and all its children pre-
   *   rendered, or FALSE if no cached copy of the element is available.
   *
   * @see \Drupal\Core\Render\RendererInterface::render()
   * @see ::set()
   */
  public function get(array $elements);

  /**
   * Caches the rendered output of a renderable array.
   *
   * May be called by an implementation of \Drupal\Core\Render\RendererInterface
   * while rendering, if the #cache property is set.
   *
   * @param array $elements
   *   A renderable array.
   * @param array $pre_bubbling_elements
   *   A renderable array corresponding to the state (in particular, the
   *   cacheability metadata) of $elements prior to the beginning of its
   *   rendering process, and therefore before any bubbling of child
   *   information has taken place. Only the #cache property is used by this
   *   function, so the caller may omit all other properties and children from
   *   this array.
   *
   * @return bool|null
   *  Returns FALSE if no cache item could be created, NULL otherwise.
   *
   * @see ::get()
   */
  public function set(array &$elements, array $pre_bubbling_elements);

}
