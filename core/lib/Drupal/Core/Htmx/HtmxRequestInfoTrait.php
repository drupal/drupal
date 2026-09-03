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
   * Retrieves the target identifier from an HTMX request header.
   *
   * HTMX 4 uses encodeURI to safely encode multilingual attribute identifiers
   * for HX-Target.  We follow that convention but use the name attribute
   * or the data-drupal-selector value when available rather than id.
   *
   * Values will be a CSS selector constructed from
   * the first available property:
   * - button[name="first_item"]
   * - button[data-drupal-selector="first_item"]
   * - button#id-value
   * - button
   *
   * @see core/assets/vendor/htmx/htmx.js:Htmx.#createRequestContext
   * @see core/misc/htmx/htmx-assets.js:htmx_config_request()
   *
   * @return string
   *   The value of the 'HX-Target' header, or an empty string if not set.
   */
  protected function getHtmxTarget(): string {
    return rawurldecode($this->getRequest()->headers->get('HX-Target', ''));
  }

  /**
   * Retrieves the trigger identifier from an HTMX request header.
   *
   * HTMX 4 uses encodeURI to safely encode multilingual attribute identifiers
   * for HX-Source.  We follow that convention but use the name attribute
   * or the data-drupal-selector value when available rather than id.
   *
   * Values will be a CSS selector constructed from
   * the first available property:
   * - button[name="first_item"]
   * - button[data-drupal-selector="first_item"]
   * - button#id-value
   * - button
   *
   * @see core/assets/vendor/htmx/htmx.js:Htmx.#createCoreHeaders
   * @see core/misc/htmx/htmx-assets.js:htmx_config_request()
   *
   * @return string
   *   The value of the 'HX-Source' header, or an empty string if not set.
   */
  protected function getHtmxSource(): string {
    return rawurldecode($this->getRequest()->headers->get('HX-Source', ''));
  }

  /**
   * Extracts the trigger name from the HX-Source header.
   *
   * @see core/assets/vendor/htmx/htmx.js:Htmx.#buildIdentifier
   *
   * @return string
   *   The value of the name attribute from the triggering element if present.
   */
  protected function getHtmxTriggerName(): string {
    $value = $this->getHtmxSource();
    // Match any characters passed as name value.
    preg_match('#name="([\s\S]+)"#', $value, $matches);
    // $matches[1] contains the name string or an empty string.
    // encodeURI passes through the + character, so use rawurldecode().
    return rawurldecode($matches[1] ?? '');
  }

  /**
   * Retrieves the request type from an HTMX request header.
   *
   * Expected values are:
   * - "full"
   * - "partial"
   *
   * @see https://four.htmx.org/reference/headers/HX-Request-Type
   *
   * @return string
   *   The value of the 'HX-Request-Type' header, or an empty string if not set.
   */
  protected function getHtmxRequestType(): string {
    return $this->getRequest()->headers->get('HX-Request-Type', '');
  }

}
