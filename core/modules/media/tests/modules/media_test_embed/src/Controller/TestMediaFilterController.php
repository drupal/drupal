<?php

namespace Drupal\media_test_embed\Controller;

use Drupal\filter\FilterFormatInterface;
use Drupal\media\Controller\MediaFilterController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller to allow testing of error handling in drupalmedia plugin.js.
 */
class TestMediaFilterController extends MediaFilterController {

  /**
   * {@inheritdoc}
   */
  public function preview(Request $request, FilterFormatInterface $filter_format) {
    if (\Drupal::state()->get('test_media_filter_controller_throw_error', FALSE)) {
      throw new NotFoundHttpException();
    }
    return parent::preview($request, $filter_format);
  }

}
