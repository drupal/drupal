<?php

namespace Drupal\Core\Session;

/**
 * Indicates that sessions for this response should be kept open after sending.
 *
 * By default, Drupal closes sessions as soon as the response is sent. If
 * a response implements this interface, Drupal will skip this behavior and
 * assume that the session will be closed manually later in the request.
 *
 * @see Drupal\Core\StackMiddleware\Session
 * @see Drupal\big_pipe\src\Render\BigPipeResponse
 * @internal
 */
interface ResponseKeepSessionOpenInterface {}
