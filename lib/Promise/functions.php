<?php declare (strict_types=1);

namespace Sabre\Event\Promise;

use Sabre\Event\Promise;
use Throwable;

/**
 * This file contains a set of functions that are useful for dealing with the
 * Promise object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */

/**
 * @param iterable $iterable
 * @return \Iterator
 */
function toIterator($iterable): \Iterator {
    
}



/**
 * This function takes an iterable of Promises, and returns a Promise that
 * resolves when all of the given arguments have resolved.
 *
 * The returned Promise will resolve with a value that's an array of all the
 * values the given promises have been resolved with.
 *
 * This array will have the exact same keys of the iterable of input promises.
 *
 * If any of the given Promises fails, the returned promise will immidiately
 * fail with the first Promise that fails, and its reason.
 *
 * @param iterable $promises iterable (array or Traversable) of Promises
 */
function all($promises) : Promise {

    return new Promise(function($success, $fail) use ($promises) {
        if (\is_array($iterable)) {
            $iterable = new \ArrayIterator($iterable);
        } else if ($iterable instanceof \IteratorAggregate) {
            $iterable = $iterable->getIterator();
        } else if (!($iterable instanceof \Iterator)) {
            throw new \InvalidArgumentException('Not an iterable');
        }

        $promises->rewind();
        if (!$promises->valid()) {
            $success([]);
            return;
        }

        $successCount = 0;
        $totalCount = 0;
        $completeResult = [];

        while ($promises->valid()) {
            $totalCount++;
            $promises->current()->then(
                function($result) use ($promiseIndex, &$completeResult, &$successCount, &$totalCount, $success, $promises) {
                    $completeResult[$promises->key()] = $result;
                    $successCount++;
                    if ($successCount === $totalCount) {
                        $success($completeResult);
                    }
                }
            )->otherwise(
                function($reason) use ($fail) {
                    $fail($reason);
                }
            );

        }
    });

}

/**
 * The race function returns a promise that resolves or rejects as soon as
 * one of the promises in the argument resolves or rejects.
 *
 * The returned promise will resolve or reject with the value or reason of
 * that first promise.
 *
 * @param iterable $promises An iterable (array or Traversable) of promises
 */
function race($promises) : Promise {

    return new Promise(function($success, $fail) use ($promises) {

        $alreadyDone = false;
        foreach ($promises as $promise) {

            $promise->then(
                function($result) use ($success, &$alreadyDone) {
                    if ($alreadyDone) {
                        return;
                    }
                    $alreadyDone = true;
                    $success($result);
                },
                function($reason) use ($fail, &$alreadyDone) {
                    if ($alreadyDone) {
                        return;
                    }
                    $alreadyDone = true;
                    $fail($reason);
                }
            );

        }

    });

}


/**
 * Returns a Promise that resolves with the given value.
 *
 * If the value is a promise, the returned promise will attach itself to that
 * promise and eventually get the same state as the followed promise.
 *
 * @param mixed $value
 */
function resolve($value) : Promise {

    if ($value instanceof Promise) {
        return $value->then();
    } else {
        $promise = new Promise();
        $promise->fulfill($value);
        return $promise;
    }

}

/**
 * Returns a Promise that will reject with the given reason.
 */
function reject(Throwable $reason) : Promise {

    $promise = new Promise();
    $promise->reject($reason);
    return $promise;

}
