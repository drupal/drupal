<?php

namespace Drupal\layout_builder;

/**
 * Provides methods for enabling and disabling Layout Builder.
 */
interface LayoutBuilderEnabledInterface {

  /**
   * Determines if Layout Builder is enabled.
   *
   * @return bool
   *   TRUE if Layout Builder is enabled, FALSE otherwise.
   */
  public function isLayoutBuilderEnabled();

  /**
   * Enables the Layout Builder.
   *
   * @return $this
   */
  public function enableLayoutBuilder();

  /**
   * Disables the Layout Builder.
   *
   * @return $this
   */
  public function disableLayoutBuilder();

}
