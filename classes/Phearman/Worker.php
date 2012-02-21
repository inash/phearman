<?php

/**
 * Main Phearman Worker class. This is the primary class to setup Gearman
 * workers, attach functions to it and set it to work under a run loop.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 */

namespace Phearman;
use Phearman\Exception;
use Phearman\Adapter;
use Phearman\Task\Request\CanDo;
use Phearman\Task\Request\GrabJob;
use Phearman\Task\Request\PreSleep;
use Phearman\Task\Request\WorkComplete;

use Phearman\Task\Request\EchoReq;

class Worker extends Adapter
{
    /**
     * Holds the registered functions with the worker.
     *
     * It is used to submit the workers capabilities to server and to lookup
     * which function to execute based on the requested job assignment
     * capability.
     *
     * @access private
     * @var $functions array
     */
    private $functions = array();

    /**
     * Boolean flag to determine whether to log messages.
     *
     * @access private
     * @var $debug boolean
     */
    private $debug = false;

    /**
     * Register a capability with a given function with the server.
     *
     * If the $functionName argument is not provided, the method assumes that
     * you want to register a function with the same name as a job or a
     * capability.
     *
     * If the provided function name or capability is not already defined as
     * a valid function, this method throws an exception.
     *
     * @access public
     * @param $jobName string
     * @param $functionName string
     * @throws Phearman\Exception
     * @void
     */
    public function addFunction($jobName, $functionName = null)
    {
        /* If functionName is not provided, assume jobName and functionName
         * are the same. */
        if ($functionName == null) $functionName = $jobName;

        /* Check if function name exists. */
        if (!function_exists($functionName))
            throw new Exception(
                'Cannot add non-existent function ' . $functionName . '.');

        /* Add function to the instance functions list. */
        $this->functions[$jobName] = $functionName;
    }

    public function work()
    {
        $this->log('Starting work.');

        /* Submit capabilities to the server. */
        foreach ($this->functions as $jobName => $functionName) {

            /* Prepare can do packet to send to the server. */
            $task = new CanDo($jobName);

            /* Send the task to the server. */
            $this->log('Registering capability ' . $jobName . ' with server.');
            $this->send($task);
        }

        while (true) {

            /* Now send a grabJob request and wait for a response. */
            $this->log('Grabing job from the server.');
            $this->send(new GrabJob());

            while (true) {

                /* Check for job from the job server. */
                $job = $this->checkForJob();

                /* Check if response is a wake up call (NOOP) from the server.
                 * If so, continue the parent loop process, starting by grabbing
                 * a new job from the server. */
                if (in_array($job->getType(), array(
                Phearman::TYPE_NOOP,
                Phearman::TYPE_JOB_ASSIGN)))
                    continue 2;
            }
        }
    }

    private function checkForJob()
    {
        /* Read response from server. */
        $job = $this->read();
        $this->log('Received response ' . $job->getTypeName() . '.');

        switch ($job->getType()) {

            /* Sleep if the response is a no job packet. */
            case Phearman::TYPE_NO_JOB:
                $this->log('Sending request PreSleep.');
                $task = new PreSleep();
                $this->send($task);
                break;

            /* Check if response is a job assignment */
            case Phearman::TYPE_JOB_ASSIGN:
                $this->log(
                    'Executing job ' . $job->getFunctionName() . ' with job '
                  . 'handle: ' . $job->getJobHandle() . '.');

                /* Call the function and do the job. */
                $output = call_user_func($job->getFunctionName(), $job);

                /* Create a work complete request from the work. */
                $task = new WorkComplete($job->getJobHandle());
                $task->setWorkload($output);
                $this->send($task);
                break;
        }

        return $job;
    }

    /**
     * Sets the instance variable debug to true.
     *
     * This flag is used by the log method below to determine whether to
     * print diagnostics to the standard output.
     *
     * @public
     * @param $boolean boolean
     * @void
     */
    public function setDebug($boolean = true)
    {
        $this->debug = $boolean;
    }

    /**
     * Used to print diagnostics to the standard output.
     *
     * @private
     * @param $message string...
     * @void.
     */
    private function log($message)
    {
        if ($this->debug != true) return;
        echo '[' . date('H:i:s.u') . '] ' . $message, PHP_EOL;
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
}
