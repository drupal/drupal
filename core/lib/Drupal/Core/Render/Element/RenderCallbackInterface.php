<?php

namespace Drupal\Core\Render\Element;

/**
 * Indicates all public methods are safe to use in render callbacks.
 *
 * This should only be used when all public methods on the class are supposed to
 * used as render callbacks or the class implements ElementInterface. If this is
 * not the case then use TrustedCallbackInterface instead.
 *
 * @see \Drupal\Core\Render\Element\ElementInterface
 * @see \Drupal\Core\Security\TrustedCallbackInterface
 * @see \Drupal\Core\Render\Renderer::doCallback()
 */
interface RenderCallbackInterface {
}
