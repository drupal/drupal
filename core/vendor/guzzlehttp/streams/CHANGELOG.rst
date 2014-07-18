=========
Changelog
=========

1.3.0 (2014-07-15)
------------------

* Added an AppendStream to stream over multiple stream one after the other.

1.2.0 (2014-07-15)
------------------

* Updated the ``detach()`` method to return the underlying stream resource or
  ``null`` if it does not wrap a resource.
* Multiple fixes for how streams behave when the underlying resource is
  detached
* Do not clear statcache when a stream does not have a 'uri'
* Added a fix to LimitStream
* Added a condition to ensure that functions.php can be required multiple times
