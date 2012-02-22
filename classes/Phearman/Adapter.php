<?php

/**
 * Abstract Adapter class.
 *
 * Concrete adapters should inherit from this abstract adapter class. Because
 * we're implementing the strategy pattern for the adapters.
 *
 * Based on the configuration, the client or worker classes will choose a
 * specific adapter. Intended target adapters are file (which uses the default
 * PHP streams), socket (implementing the socket API) and perhaps cURL.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman;
use Phearman\Exception;

abstract class Adapter
{
    protected $hosts     = array();
    protected $connected = false;
    protected $resource;

    /**
     * Final constructor for the derived classes of class Connection.
     *
     * @final
     * @access public
     * @param string $hosts
     * @param string $port
     * @throws Phearman\Exception
     * @void
     */
    final public function __construct($hosts = 'localhost', $port = 4730)
    {
        /* Distinguish wether the host argument is a string or array. If it is
         * a string, we need to parse it as a URL and check whether the port
         * is provided along with it, so as to ignore the second $port
         * argument. */
        if (is_string($hosts)) {
            $url = parse_url($hosts);

            /* Add default port or the provided port argument if the host
             * string did not already have it with a ':' separator. */
            if (!isset($url['port'])) $hosts .= ':' . $port;
            $hosts = array($hosts);
        }

        /* Throw exception if an invalid type has been provided as a host
         * name. */
        if (!is_array($hosts))
            throw new Exception(
                'Invalid host name. Please provide a single string or an array '
              . 'of string host names (provide port optionally separated by a '
              . 'colon).');

        /* Process the host argument as an array and set them to the private
         * hosts instance variable. */
        foreach ($hosts as $key => $host) {
            $url = parse_url($host);

            if (!isset($url['port'])) $host .= ':' . $port;
            $this->hosts[] = $host;
        }
    }

    final public function addServer($host)
    {
        if (!in_array($host, $this->hosts))
            $this->hosts[] = $host;
    }

    abstract protected function connect();
    abstract public function write($task);
    abstract public function read();
    abstract public function close();
}
