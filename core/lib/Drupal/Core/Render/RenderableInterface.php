<?php

namespace Drupal\Core\Render;

/**
 * Defines an object which can be rendered by the Render API.
 */
interface RenderableInterface {

  /**
   * Returns a render array representation of the object.
   *
   * @return mixed[]
   *   A render array.
   */
  public function toRenderable();

}
