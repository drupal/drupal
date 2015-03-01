<?php

namespace React\Promise;

function resolve($promiseOrValue = null)
{
    if (!$promiseOrValue instanceof PromiseInterface) {
        return new FulfilledPromise($promiseOrValue);
    }

    if ($promiseOrValue instanceof ExtendedPromiseInterface) {
        return $promiseOrValue;
    }

    return new Promise(function ($resolve, $reject, $notify) use ($promiseOrValue) {
        $promiseOrValue->then($resolve, $reject, $notify);
    });
}

function reject($promiseOrValue = null)
{
    if ($promiseOrValue instanceof PromiseInterface) {
        return resolve($promiseOrValue)->then(function ($value) {
            return new RejectedPromise($value);
        });
    }

    return new RejectedPromise($promiseOrValue);
}

function all($promisesOrValues)
{
    return map($promisesOrValues, function ($val) {
        return $val;
    });
}

function race($promisesOrValues)
{
    return resolve($promisesOrValues)
        ->then(function ($array) {
            if (!is_array($array) || !$array) {
                return resolve();
            }

            return new Promise(function ($resolve, $reject, $notify) use ($array) {
                foreach ($array as $promiseOrValue) {
                    resolve($promiseOrValue)
                        ->done($resolve, $reject, $notify);
                }
            });
        });
}

function any($promisesOrValues)
{
    return some($promisesOrValues, 1)
        ->then(function ($val) {
            return array_shift($val);
        });
}

function some($promisesOrValues, $howMany)
{
    return resolve($promisesOrValues)
        ->then(function ($array) use ($howMany) {
            if (!is_array($array) || !$array || $howMany < 1) {
                return resolve([]);
            }

            return new Promise(function ($resolve, $reject, $notify) use ($array, $howMany) {
                $len       = count($array);
                $toResolve = min($howMany, $len);
                $toReject  = ($len - $toResolve) + 1;
                $values    = [];
                $reasons   = [];

                foreach ($array as $i => $promiseOrValue) {
                    $fulfiller = function ($val) use ($i, &$values, &$toResolve, $toReject, $resolve) {
                        if ($toResolve < 1 || $toReject < 1) {
                            return;
                        }

                        $values[$i] = $val;

                        if (0 === --$toResolve) {
                            $resolve($values);
                        }
                    };

                    $rejecter = function ($reason) use ($i, &$reasons, &$toReject, $toResolve, $reject) {
                        if ($toResolve < 1 || $toReject < 1) {
                            return;
                        }

                        $reasons[$i] = $reason;

                        if (0 === --$toReject) {
                            $reject($reasons);
                        }
                    };

                    resolve($promiseOrValue)
                        ->done($fulfiller, $rejecter, $notify);
                }
            });
        });
}

function map($promisesOrValues, callable $mapFunc)
{
    return resolve($promisesOrValues)
        ->then(function ($array) use ($mapFunc) {
            if (!is_array($array) || !$array) {
                return resolve([]);
            }

            return new Promise(function ($resolve, $reject, $notify) use ($array, $mapFunc) {
                $toResolve = count($array);
                $values    = [];

                foreach ($array as $i => $promiseOrValue) {
                    resolve($promiseOrValue)
                        ->then($mapFunc)
                        ->done(
                            function ($mapped) use ($i, &$values, &$toResolve, $resolve) {
                                $values[$i] = $mapped;

                                if (0 === --$toResolve) {
                                    $resolve($values);
                                }
                            },
                            $reject,
                            $notify
                        );
                }
            });
        });
}

function reduce($promisesOrValues, callable $reduceFunc , $initialValue = null)
{
    return resolve($promisesOrValues)
        ->then(function ($array) use ($reduceFunc, $initialValue) {
            if (!is_array($array)) {
                $array = [];
            }

            $total = count($array);
            $i = 0;

            // Wrap the supplied $reduceFunc with one that handles promises and then
            // delegates to the supplied.
            $wrappedReduceFunc = function ($current, $val) use ($reduceFunc, $total, &$i) {
                return resolve($current)
                    ->then(function ($c) use ($reduceFunc, $total, &$i, $val) {
                        return resolve($val)
                            ->then(function ($value) use ($reduceFunc, $total, &$i, $c) {
                                return $reduceFunc($c, $value, $i++, $total);
                            });
                    });
            };

            return array_reduce($array, $wrappedReduceFunc, $initialValue);
        });
}

// Internal functions
function _checkTypehint(callable $callback, $object)
{
    if (!is_object($object)) {
        return true;
    }

    if (is_array($callback)) {
        $callbackReflection = new \ReflectionMethod($callback[0], $callback[1]);
    } elseif (is_object($callback) && !$callback instanceof \Closure) {
        $callbackReflection = new \ReflectionMethod($callback, '__invoke');
    } else {
        $callbackReflection = new \ReflectionFunction($callback);
    }

    $parameters = $callbackReflection->getParameters();

    if (!isset($parameters[0])) {
        return true;
    }

    $expectedException = $parameters[0];

    if (!$expectedException->getClass()) {
        return true;
    }

    return $expectedException->getClass()->isInstance($object);
}
