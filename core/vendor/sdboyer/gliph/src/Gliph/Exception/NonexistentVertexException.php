<?php

/**
 * @file
 * Contains \Gliph\Exception\NonexistentVertexException.
 */

namespace Gliph\Exception;

/**
 * Exception thrown when a vertex not present in a Graph is provided as a
 * parameter to a method that requires the vertex to be present (e.g., removing
 * the vertex, checking the edges of that vertex).
 */
class NonexistentVertexException extends \OutOfBoundsException {}