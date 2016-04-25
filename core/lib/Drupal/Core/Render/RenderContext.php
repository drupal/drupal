<?php

namespace Drupal\Core\Render;

/**
 * The render context: a stack containing bubbleable rendering metadata.
 *
 * A stack of \Drupal\Core\Render\BubbleableMetadata objects.
 *
 * @see \Drupal\Core\Render\RendererInterface
 * @see \Drupal\Core\Render\Renderer
 * @see \Drupal\Core\Render\BubbleableMetadata
 *
 * @internal
 */
class RenderContext extends \SplStack {

  /**
   * Updates the current frame of the stack.
   *
   * @param array &$element
   *   The element of the render array that has just been rendered. The stack
   *   frame for this element will be updated with the bubbleable rendering
   *   metadata of this element.
   */
  public function update(&$element) {
    // The latest frame represents the bubbleable metadata for the subtree.
    $frame = $this->pop();
    // Update the frame, but also update the current element, to ensure it
    // contains up-to-date information in case it gets render cached.
    $updated_frame = BubbleableMetadata::createFromRenderArray($element)->merge($frame);
    $updated_frame->applyTo($element);
    $this->push($updated_frame);
  }

  /**
   * Bubbles the stack.
   *
   * Whenever another level in the render array has been rendered, the stack
   * must be bubbled, to merge its rendering metadata with that of the parent
   * element.
   */
  public function bubble() {
    // If there's only one frame on the stack, then this is the root call, and
    // we can't bubble up further. ::renderRoot() will reset the stack, but we
    // must not reset it here to allow users of ::executeInRenderContext() to
    // access the stack directly.
    if ($this->count() === 1) {
      return;
    }

    // Merge the current and the parent stack frame.
    $current = $this->pop();
    $parent = $this->pop();
    $this->push($current->merge($parent));
  }

}
