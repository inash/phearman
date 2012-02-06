<?php

/**
 * Main Phearman Client class. This class is primarily used to submit jobs
 * to the Gearman job server.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 */

namespace Phearman;
use Phearman\Task\Request\SubmitJob;
use Phearman\Exception;

class Client
{
    private $hosts = array();
    private $connected = false;
    private $socket;
    private $lastTask = null;
    private $lastTaskResponses = array();

    public function __construct($host = 'localhost', $port = 4730)
    {
        $this->socket = fsockopen($host, $port);
    }

    public function addServer($host)
    {
        if (!in_array($host, $this->hosts))
            $this->hosts[] = $host;
    }

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
