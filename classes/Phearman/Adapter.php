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
    private $hosts = array();
    private $connected = false;
    private $socket;

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
    final public function __construct(
        $hosts = 'localhost', $port = 4730, $adapter = 'file')
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

    public function addServer($host)
    {
        if (!in_array($host, $this->hosts))
            $this->hosts[] = $host;
    }

    private function connect()
    {
        $errorNumber = $errorString = null;

        /* Check if we're able to connect either of the provided servers upon
         * instantiation. */
        foreach ($this->hosts as $host) {
            $url = parse_url($host);
            $this->socket = @fsockopen(
                $url['host'], $url['port'], $errorNumber, $errorString);

            /* Break if connection succeeded with no error and no message. */
            if ($errorNumber == 0 && $errorString == '') {
                $this->connected = true;
                break;
            }
        }

        /* Handle connection errors. */
        if (!$this->socket) {
            $errorString = sprintf(
                'Could not connect to host %s:%s (last host in the chain). '
              . 'Reason: %s',
              $url['host'], $url['port'], $errorString);
            throw new Exception($errorString, $errorNumber);
        }
    }

    protected function send($task)
    {
        /* Check if adapter is connected. If not, then make the connection
         * prior to proceeding. */
        if ($this->connected === false)
            $this->connect();

        // TODO
        fwrite($this->socket, $task);
    }

    protected function read()
    {
        /* Check if adapter is connected. If not, then make the connection
         * prior to reading any data. */
        if ($this->connected === false)
            $this->connect();

        $code = null;
        while ($code == null) {
            $code   = fread($this->socket, 4);
            $header = fread($this->socket, 8);
        }

        /* Unpack the header segment and assign them as type and length. */
        $header = unpack('N2', $header);
        $type   = $header[1];
        $length = $header[2];

        /* Create a task using the Task::factory method based on the received
         * packet type and code. Here code is used for the factory method
         * to map it conveniently to the specific class folders (Request/
         * Response). */
        $task = Task::factory($type, Phearman::CODE_RESPONSE);
        $task->setLength($length);

        /* Return the task if there is no data part associated with the
         * received message. */
        if ($length == 0) return $task;

        /* Break up data parts with the workload. */
        $packet = fread($this->socket, $length);
        $task->setFromResponse($packet);

        return $task;
    }
}
