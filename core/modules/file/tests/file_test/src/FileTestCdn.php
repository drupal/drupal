<?php

declare(strict_types=1);

namespace Drupal\file_test;

/**
 * Test Cdn.
 */
enum FileTestCdn: string {

  /*
   * First Cdn.
   */
  case First = 'http://cdn1.example.com';

  /*
   * Second Cdn.
   */
  case Second = 'http://cdn2.example.com';

}
