<?php

namespace oihana\magento\exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a Magento REST call finally fails: every retry is exhausted,
 * the error is not retryable, or the response cannot be decoded.
 *
 * It tells "Magento did not answer properly" apart from "Magento has nothing":
 * a caller that removes whatever it did not receive must never mistake an
 * outage for an empty result.
 *
 * The HTTP status of the last response is exposed through `getCode()`, or
 * {@see MagentoRequestException::NO_RESPONSE} when no response arrived
 * (timeout, refused connection, unknown host). The transport exception, when
 * there is one, is chained as the previous exception.
 *
 * `401` and `404` keep their dedicated exceptions (`Error401`, `Error404`).
 *
 * @package oihana\magento\exceptions
 */
class MagentoRequestException extends RuntimeException
{
    /**
     * Creates a new MagentoRequestException instance.
     *
     * @param string         $message    The failure message.
     * @param int            $statusCode The HTTP status of the last response, or {@see NO_RESPONSE}.
     * @param Throwable|null $previous   The transport or decoding exception, if any.
     */
    public function __construct( string $message = '' , int $statusCode = self::NO_RESPONSE , ?Throwable $previous = null )
    {
        parent::__construct( $message , $statusCode , $previous ) ;
    }

    /**
     * The code carried when no HTTP response was received.
     */
    public const int NO_RESPONSE = 0 ;
}
