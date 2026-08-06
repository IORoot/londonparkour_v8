<?php

namespace MatthiasWeb\RealMediaLibrary\exception;

use Exception;
// @codeCoverageIgnoreStart
\defined('ABSPATH') or die('No script kiddies please!');
// Avoid direct file request
// @codeCoverageIgnoreEnd
/**
 * When a functionality is only available in PRO version throw an exception.
 * @internal
 */
class OnlyInProVersionException extends Exception
{
    /**
     * C'tor.
     *
     * @param string $method
     */
    public function __construct($method)
    {
        // translators:
        parent::__construct(\sprintf(
            // translators:
            \__('This functionality is not available in the free version (%s).', 'real-media-library-lite'),
            $method
        ));
    }
}
