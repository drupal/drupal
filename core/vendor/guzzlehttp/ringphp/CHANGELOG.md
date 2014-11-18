# CHANGELOG

## 1.0.3 - 2014-11-03

* Setting the `header` stream option as a string to be compatible with GAE.
* Header parsing now ensures that header order is maintained in the parsed
  message.

## 1.0.2 - 2014-10-28

* Now correctly honoring a `version` option is supplied in a request.
  See https://github.com/guzzle/RingPHP/pull/8

## 1.0.1 - 2014-10-26

* Fixed a header parsing issue with the `CurlHandler` and `CurlMultiHandler`
  that caused cURL requests with multiple responses to merge repsonses together
  (e.g., requests with digest authentication).

## 1.0.0 - 2014-10-12

* Initial release.
