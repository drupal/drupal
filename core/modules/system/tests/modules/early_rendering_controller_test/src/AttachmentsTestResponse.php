<?php

declare(strict_types=1);

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test response with implemented AttachmentsInterface.
 */
class AttachmentsTestResponse extends Response implements AttachmentsInterface {

  use AttachmentsTrait;

}
