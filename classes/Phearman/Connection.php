<?php

namespace Phearman;
use Phearman\Exception;
use Phearman\Task\Request\EchoReq;

/**
 * This is the abstract connection class inherited by the client and worker.
 *
 * Connection implements an abstract model for an endpoint in a German client/
 * worker. It also implements methods for common request packets such as
 * Echo, etc. Enabling debug options for diagnostics for a client or a worker
 * is also implemented by the Connection class.
 *
 * @abstract
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
abstract class Connection
{
    /**
     * The adapter instance once set through the setAdapter method.
     *
     * @access protected
     * @var Phearman\Adapter
     */
    protected $adapter;

    /**
     * Boolean flag to determine whether to log messages.
     *
     * @access private
     * @var boolean
     */
    protected $debug = false;

    /**
     * Final constructor method.
     *
     * We do not want any subclasses to override the basic constructor method,
     * instead it can implement separate configuration methods independantly.
     *
     * @final
     * @access public
     * @param string|array $hosts
     * @param integer $port
     * @param string|Phearman\Adapter $adapter
     */
    final public function __construct(
        $hosts = 'localhost', $port = 4730, $adapter = 'socket')
    {
        $this->setAdapter($adapter, $hosts, $port);
    }

    /**
     * Accessor method to retrieve the adapter.
     *
     * Use this method to change the adapter settings or if you need to do
     * some custom advanced communication that the client or the worker does
     * not already provide.
     *
     * @final
     * @access public
     * @return Phearman\Adapter
     */
    final public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Accessor method to set a new adapter.
     *
     * This method sets a new adapter. You can either provide the name of an
     * adapter or provide an instance of an adapter pre-configured.
     *
     * @final
     * @access public
     * @param string|Phearman\Adapter $adapter
     * @param string $hosts
     * @param integer $port
     * @throws Phearman\Exception
     */
    final public function setAdapter($adapter, $hosts = 'localhost', $port = 4730)
    {
        /* Check if the provided adapter is an object and an instance of the
         * class Phearman\Adapter, and set it directly if it is so. */
        if (is_object($adapter) && $adapter instanceOf Phearman\Adapter) {
            $this->adapter = $adapter;
            return;
        }

        /* Resolve adapter name to the adapter class. */
        $adapter   = ucfirst($adapter);
        $className = 'Phearman\\Adapter\\' . $adapter;

        /* Check if the adapter class exists. Otherwise throw an error. */
        if (!class_exists($className))
            throw new Exception('Invalid adapter ' . $adapter . '.');

        /* Instantiate and set the adapter class. */
        $this->adapter = new $className($hosts, $port);
    }

    /**
     * Sends an ECHO_REQ request to the server.
     *
     * The request is immediately replied back with ECHO_RES with the request
     * workload.
     *
     * @access public
     * @param string $data
     * @return \Phearman\Task\Response\EchoRes
     */
    public function echoRequest($data)
    {
        /* Create a new EchoReq packet, send it to the server, read the response
         * and return it. */
        $this->adapter->write(new EchoReq($data));
        $this->log('> ECHO_REQ.');

        $response = $this->adapter->read();
        $this->log('< ' . $response->getTypeName() . '.');

        return $response;
    }

    /**
     * Sets the instance variable debug to true.
     *
     * This flag is used by the log method below to determine whether to
     * print diagnostics to the standard output.
     *
     * @access public
     * @param boolean $boolean
     */
    public function setDebug($boolean = true)
    {
        $this->debug = $boolean;
    }

    /**
     * Used to print diagnostics to the standard output.
     *
     * @access private
     * @param string $message
     */
    protected function log($message)
    {
        if ($this->debug != true) return;
        $class = str_replace('Phearman\\', '', get_class($this));
        $class = strtoupper($class);
        echo date('[H:i:s.u] ') . $class . ': ' . $message, PHP_EOL;
    }
}
