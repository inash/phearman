<?php

/**
 * Main Phearman Worker class. This is the primary class to setup Gearman
 * workers, attach functions to it and set it to work under a run loop.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman;
use Phearman\Exception;
use Phearman\Connection;
use Phearman\Task\Request\CanDo;
use Phearman\Task\Request\GrabJob;
use Phearman\Task\Request\PreSleep;
use Phearman\Task\Request\WorkComplete;
use Phearman\Task\Request\EchoReq;

class Worker extends Connection
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

    /**
     * Update the status of a running job.
     *
     * This method sends the WORK_STATUS request to the server given a job
     * handle, percent complete numerator and percent complete denominator.
     *
     * While executing long running jobs, it might be necessary to send the
     * progress to the client. As the work progresses this method can be
     * called through the second argument passed to the job function (which is
     * the worker itself) providing the job handle and the progress details.
     *
     * e.g:
     * <code>
     * function exampleJob($job, $worker) {
     *     $jobHandle = $job->getJobHandle();
     *     $worker->updateStatus($jobHandle, 33, 100);
     *     $worker->updateStatus($jobHandle, 66, 100);
     *     $worker->updateStatus($jobHandle, 100, 100);
     *     return 'Job completed successfully.';
     * }
     * </code>
     *
     * @access public
     * @param $jobHandle string
     * @param $percentNum integer
     * @param $percentDen integer
     * @void
     */
    public function updateStatus($jobHandle, $percentNum, $percentDen)
    {
        $task = new Task\Request\WorkStatus(
            $jobHandle, $percentNum, $percentDen);
        $this->adapter->write($task);
        $this->log("> WORK_STATUS {$jobHandle} {$percentNum}/{$percentDen}.");
    }

    /**
     * Set the worker to work.
     *
     * This method connects to the worker to a German server, submits it's
     * capabilities and goes into the GRAB_JOB loop. When a job is returned
     * the function associated with the job will be executed.
     *
     * @access public
     * @void
     */
    public function work()
    {
        $this->log('+ Starting work.');

        /* Submit capabilities to the server. */
        foreach ($this->functions as $jobName => $functionName) {

            /* Prepare can do packet to send to the server. */
            $task = new CanDo($jobName);

            /* Send the task to the server. */
            $this->log("+ Registering capability {$jobName} with server.");
            $this->adapter->write($task);
        }

        /* Main loop to check for jobs. */
        while (true) {

            /* Now send a grabJob request and wait for a response. */
            $this->log('> GRAB_JOB.');
            $this->adapter->write(new GrabJob());

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
        $job = $this->adapter->read();
        $this->log("< {$job->getTypeName()}.");

        switch ($job->getType()) {

            /* Sleep if the response is a no job packet. */
            case Phearman::TYPE_NO_JOB:
                $this->log('> PRE_SLEEP.');
                $task = new PreSleep();
                $this->adapter->write($task);
                break;

            /* Check if response is a job assignment */
            case Phearman::TYPE_JOB_ASSIGN:
                $this->log(sprintf(
                    '* %s %s.', $job->getFunctionName(), $job->getJobHandle()));

                /* Call the function and do the job. */
                $output = call_user_func($job->getFunctionName(), $job, $this);

                /* Create a work complete request from the work. */
                $task = new WorkComplete($job->getJobHandle());
                $task->setWorkload($output);
                $this->adapter->write($task);
                $this->log("> WORK_COMPLETE {$job->getJobHandle()}.");
                break;
        }

        return $job;
    }
}
