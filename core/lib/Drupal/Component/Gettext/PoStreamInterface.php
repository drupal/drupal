<?php

/**
 * @file
 * Definition of Drupal\Component\Gettext\PoStreamInterface.
 */

namespace Drupal\Component\Gettext;

/**
 * Common functions for file/stream based PO readers/writers.
 *
 * @see PoReaderInterface
 * @see PoWriterInterface
 */
interface PoStreamInterface {

  /**
   * Open the stream. Set the URI for the stream earlier with setURI().
   */
  function open();

  /**
   * Close the stream.
   */
  function close();

  /**
   * Get the URI of the PO stream that is being read or written.
   *
   * @return
   *   URI string for this stream.
   */
  function getURI();

  /**
   * Set the URI of the PO stream that is going to be read or written.
   *
   * @param $uri
   *   URI string to set for this stream.
   */
  function setURI($uri);
}
