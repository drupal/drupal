<?php

declare(strict_types=1);

namespace Drupal\Core\Htmx;

/**
 * Provides methods for getting information about the HTMX request.
 */
trait HtmxRequestInfoTrait {

  /**
   * Gets the request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  abstract protected function getRequest();

  /**
   * Determines if the request is sent by HTMX.
   *
   * @return bool
   *   TRUE if the 'HX-Request' header is present.
   */
  protected function isHtmxRequest(): bool {
    return $this->getRequest()->headers->has('HX-Request');
  }

  /**
   * Determines if the request is boosted by HTMX.
   *
   * @return bool
   *   TRUE if the 'HX-Boosted' header is present.
   */
  protected function isHtmxBoosted(): bool {
    return $this->getRequest()->headers->has('HX-Boosted');
  }

  /**
   * Retrieves the URL of the requesting page from an HTMX request header.
   *
   * @return string
   *   The value of the 'HX-Current-URL' header, or an empty string if not set.
   */
  protected function getHtmxCurrentUrl(): string {
    return $this->getRequest()->headers->get('HX-Current-URL', '');
  }

  /**
   * Determines if if the request is for history restoration.
   *
   * Sent after a miss in the local history cache
   *
   * @return bool
   *   TRUE if the 'HX-History-Restore-Request' header is present.
   */
  protected function isHtmxHistoryRestoration(): bool {
    return $this->getRequest()->headers->has('HX-History-Restore-Request');
  }

  /**
   * Retrieves the prompt from an HTMX request header.
   *
   * @return string
   *   The value of the 'HX-Prompt' header, or an empty string if not set.
   */
  protected function getHtmxPrompt(): string {
    return $this->getRequest()->headers->get('HX-Prompt', '');
  }

  /**
   * Retrieves the target identifier from an HTMX request header.
   *
   * @return string
   *   The value of the 'HX-Target' header, or an empty string if not set.
   */
  protected function getHtmxTarget(): string {
    return $this->getRequest()->headers->get('HX-Target', '');
  }

  /**
   * Retrieves the trigger identifier from an HTMX request header.
   *
   * @return string
   *   The value of the 'HX-Trigger' header, or an empty string if not set.
   */
  protected function getHtmxTrigger(): string {
    return $this->getRequest()->headers->get('HX-Trigger', '');
  }

  /**
   * Retrieves the trigger name from an HTMX request header.
   *
   * @return string
   *   The value of the 'HX-Trigger-Name' header, or an empty string if not set.
   */
  protected function getHtmxTriggerName(): string {
    return $this->getRequest()->headers->get('HX-Trigger-Name', '');
  }

}
