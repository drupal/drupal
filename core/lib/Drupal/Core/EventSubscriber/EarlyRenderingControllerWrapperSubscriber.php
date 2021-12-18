<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber that wraps controllers, to handle early rendering.
 *
 * When controllers call RendererInterface::render() outside of a render
 * context, we call that "early rendering". Controllers should return
 * only render arrays, but we cannot prevent controllers from doing
 * early rendering. The problem with early rendering is that the
 * bubbleable metadata (cacheability & attachments) are lost.
 *
 * This can lead to broken pages (missing assets), stale pages (missing cache
 * tags causing a page not to be invalidated) or even security problems (missing
 * cache contexts causing a cached page not to be varied sufficiently).
 *
 * This event subscriber wraps all controller executions in a closure that sets
 * up a render context. Consequently, any early rendering will have their
 * bubbleable metadata (assets & cacheability) stored on that render context.
 *
 * If the render context is empty, then the controller either did not do any
 * rendering at all, or used the RendererInterface::renderRoot() or
 * ::renderPlain() methods. In that case, no bubbleable metadata is lost.
 *
 * If the render context is not empty, then the controller did use
 * RendererInterface::render(), and bubbleable metadata was collected.
 * This bubbleable metadata is then merged onto the render array.
 *
 * In other words: this just exists to ease the transition to Drupal 8: it
 * allows controllers that return render arrays (the majority) and
 * \Drupal\Core\Ajax\AjaxResponse\AjaxResponse objects (a sizable minority that
 * often involve a fair amount of rendering) to still do early rendering. But
 * controllers that return any other kind of response are already expected to
 * do the right thing, so if early rendering is detected in such a case, an
 * exception is thrown.
 *
 * @see \Drupal\Core\Render\RendererInterface
 * @see \Drupal\Core\Render\Renderer
 *
 * @todo Remove in Drupal 9.0.0, by disallowing early rendering.
 */
class EarlyRenderingControllerWrapperSubscriber implements EventSubscriberInterface {

  /**
   * The argument resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface
   */
  protected $argumentResolver;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EarlyRenderingControllerWrapperSubscriber instance.
   *
   * @param \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface $argument_resolver
   *   The argument resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ArgumentResolverInterface $argument_resolver, RendererInterface $renderer) {
    $this->argumentResolver = $argument_resolver;
    $this->renderer = $renderer;
  }

  /**
   * Ensures bubbleable metadata from early rendering is not lost.
   *
   * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
   *   The controller event.
   */
  public function onController(ControllerEvent $event) {
    $controller = $event->getController();

    // See \Symfony\Component\HttpKernel\HttpKernel::handleRaw().
    $arguments = $this->argumentResolver->getArguments($event->getRequest(), $controller);

    $event->setController(function () use ($controller, $arguments) {
      return $this->wrapControllerExecutionInRenderContext($controller, $arguments);
    });
  }

  /**
   * Wraps a controller execution in a render context.
   *
   * @param callable $controller
   *   The controller to execute.
   * @param array $arguments
   *   The arguments to pass to the controller.
   *
   * @return mixed
   *   The return value of the controller.
   *
   * @throws \LogicException
   *   When early rendering has occurred in a controller that returned a
   *   Response or domain object that cares about attachments or cacheability.
   *
   * @see \Symfony\Component\HttpKernel\HttpKernel::handleRaw()
   */
  protected function wrapControllerExecutionInRenderContext($controller, array $arguments) {
    $context = new RenderContext();

    $response = $this->renderer->executeInRenderContext($context, function () use ($controller, $arguments) {
      // Now call the actual controller, just like HttpKernel does.
      return call_user_func_array($controller, $arguments);
    });

    // If early rendering happened, i.e. if code in the controller called
    // RendererInterface::render() outside of a render context, then the
    // bubbleable metadata for that is stored in the current render context.
    if (!$context->isEmpty()) {
      /** @var \Drupal\Core\Render\BubbleableMetadata $early_rendering_bubbleable_metadata */
      $early_rendering_bubbleable_metadata = $context->pop();

      // If a render array or AjaxResponse is returned by the controller, merge
      // the "lost" bubbleable metadata.
      if (is_array($response)) {
        BubbleableMetadata::createFromRenderArray($response)
          ->merge($early_rendering_bubbleable_metadata)
          ->applyTo($response);
      }
      elseif ($response instanceof AjaxResponse) {
        $response->addAttachments($early_rendering_bubbleable_metadata->getAttachments());
        // @todo Make AjaxResponse cacheable in
        //   https://www.drupal.org/node/956186. Meanwhile, allow contrib
        //   subclasses to be.
        if ($response instanceof CacheableResponseInterface) {
          $response->addCacheableDependency($early_rendering_bubbleable_metadata);
        }
      }
      // If a non-Ajax Response or domain object is returned and it cares about
      // attachments or cacheability, then throw an exception: early rendering
      // is not permitted in that case. It is the developer's responsibility
      // to not use early rendering.
      elseif ($response instanceof AttachmentsInterface || $response instanceof CacheableResponseInterface || $response instanceof CacheableDependencyInterface) {
        throw new \LogicException(sprintf('The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Please ensure you are not rendering content too early. Returned object class: %s.', get_class($response)));
      }
      else {
        // A Response or domain object is returned that does not care about
        // attachments nor cacheability; for instance, a RedirectResponse. It is
        // safe to discard any early rendering metadata.
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::CONTROLLER][] = ['onController'];

    return $events;
  }

}
