<?php

/**
 * @file
 * Contains \Drupal\early_rendering_controller_test\AttachmentsTestResponse.
 */

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;
use Symfony\Component\HttpFoundation\Response;

class AttachmentsTestResponse extends Response implements AttachmentsInterface {

  use AttachmentsTrait;

}
