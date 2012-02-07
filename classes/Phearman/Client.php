<?php

/**
 * Main Phearman Client class. This class is primarily used to submit jobs
 * to the Gearman job server.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 */

namespace Phearman;
use Phearman\Exception;
use Phearman\Task\Request\SubmitJob;
use Phearman\Task\Request\EchoReq;

class Client
{
    private $hosts = array();
    private $connected = false;
    private $socket;
    private $lastTask = null;
    private $lastTaskResponses = array();

    /**
     * Create a Gearman client to send jobs to a server.
     *
     * @access public
     * @param string $host
     * @param string $port
     * @throws Phearman\Exception
     * @void
     */
    public function __construct($host = 'localhost', $port = 4730)
    {
        $errorNumber = $errorString = null;

        $this->socket = @fsockopen($host, $port, $errorNumber, $errorString);

        /* Handle connection errors. */
        if (!$this->socket) {
            $errorString = sprintf(
                'Could not connect to host %s:%s. Reason: %s',
                $host, $port, $errorString);
            throw new Exception($errorString, $errorNumber);
        }
    }

    public function addServer($host)
    {
        if (!in_array($host, $this->hosts))
            $this->hosts[] = $host;
    }

    /**
     * Submit a job to a Gearman server.
     *
     * To use the background and priority requests, specify the optional third
     * and fourth arguments. By default it sends a SUBMIT_JOB packet.
     *
     * If a background (SUBMIT_JOB_[BG|_*_BG]) which are the asynchronous
     * requests, this returns with initial JOB_CREATED response from the server.
     * Otherwise the client waits for an additional response packet such as
     * WORK_* and returns that.
     *
     * For synchronous (non background) requests, the initial and subsequent
     * responses from the server can be accessed through the
     * Client::getLastTaskResponses() method. And the last request through
     * Client::getLastTask() method.
     *
     * @access public
     * @param string $functionName
     * @param string $workload
     * @param int    $type Refer to Phearman::TYPE_SUBMIT_JOB* constants.
     * @param string $uniqueId
     * @throws \Phearman\Exception
     * @return \Phearman\Task\Response\WorkComplete
     */
    public function submitJob(
        $functionName, $workload, $type = Phearman::TYPE_SUBMIT_JOB,
        $uniqueId = null)
    {
        /* Reset last task responses array. */
        $this->lastTaskResponses = array();

        /* Prepare current request and store it in the property lastTask. */
        $this->lastTask = new SubmitJob($uniqueId, $type);
        $this->lastTask->setFunctionName($functionName)
                          ->setWorkload($workload)
                          ->setUniqueId($uniqueId);

        /* Send the current packet over the wire. */
        $this->send($this->lastTask);

        /* Get immediate response from the Gearman server.
         * eg: JOB_CREATED, etc. */
        $task = $this->read();
        $this->lastTaskResponses[] = $task;

        /* Check for error and throw exception if an exception response was
         * returned. */
        if (!$task->getType() == Phearman::TYPE_JOB_CREATED) {
            throw new Exception('Bleh?');
        }

        /* Perform second response based on the request type. ie: if the
         * packet type is SUBMIT_JOB, SUBMIT_JOB_HIGH, SUBMIT_JOB_LOW.
         *
         * eg: WORK_DATA, WORK_WARNING, WORK_STATUS, WORK_COMPLETE, WORK_FAIL,
         * WORK_EXCEPTION. */
        if (in_array($type, array(
            Phearman::TYPE_SUBMIT_JOB,
            Phearman::TYPE_SUBMIT_JOB_HIGH,
            Phearman::TYPE_SUBMIT_JOB_LOW)
        )) {
            $response = $this->read();
            $this->lastTaskResponses[] = $response;
            return $response;
        }

        /* Return the initial response otherwise. */
        return $task;
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
        /* Reset last task responses. */
        $this->lastTaskResponses = array();

        /* Set current echo request task. */
        $this->lastTask = new EchoReq($data);

        /* Send request. */
        $this->send($this->lastTask);

        /* Get response and set it in the last task responses property. */
        $response = $this->read();
        $this->lastTaskResponses[] = $response;

        return $response;
    }

    public function send($task)
    {
        fwrite($this->socket, $task);
    }

    public function read()
    {
        $code   = fread($this->socket, 4);
        $header = fread($this->socket, 8);
        $header = unpack('N2', $header);
        $type   = $header[1];
        $length = $header[2];

        $task = Task::factory($type, Phearman::CODE_RESPONSE);
        $task->setLength($length);

        if ($length == 0) return $task;

        /* Break up data parts with the workload. */
        $packet = fread($this->socket, $length);
        $task->setFromResponse($packet);

        return $task;
    }

    public function getLastTask()
    {
        return $this->lastTask;
    }

    public function getLastTaskResponses()
    {
        return $this->lastTaskResponses;
    }

    public function getStatus($jobHandle)
    {
        //
    }

    private function connect()
    {
        //
    }

    private function isConnected()
    {
        //
    }
}
