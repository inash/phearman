<?php

namespace Phearman;
use Phearman\Exception;
use Phearman\Connection;
use Phearman\Task\Request\SubmitJob;
use Phearman\Task\Request\GetStatus;
use Phearman\Task\Request\EchoReq;

/**
 * Main Phearman Client class. This class is primarily used to submit jobs
 * to the Gearman job server.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class Client extends Connection
{
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
        /* Prepare request. */
        $task = new SubmitJob($uniqueId, $type);
        $task->setFunctionName($functionName)
                       ->setWorkload($workload)
                       ->setUniqueId($uniqueId);

        /* Send the packet over the wire. */
        $this->adapter->write($task);

        $this->log("> {$task->getTypeName()} {$functionName}.");

        /* Get immediate response from the Gearman server.
         * eg: JOB_CREATED, etc. */
        $response = $this->adapter->read();

        $this->log("< {$response->getTypeName()} {$response->getJobHandle()}.");

        /* Check for error and throw exception if an exception response was
         * returned. */
        if (!$response->getType() == Phearman::TYPE_JOB_CREATED)
            throw new Exception('Bleh?');

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
            $response = $this->adapter->read();
            return $response;
        }

        /* Return the initial response otherwise. */
        return $response;
    }

    /**
     * Returns the status of a submitted job.
     *
     * To retrieve the status of a background job, calling this method will
     * retrieve the worker response STATUS_RES with the job running status and
     * percent complete. To follow status in an interval, use this method in
     * a loop and evaluate until running state of the job is 0.
     * i.e:
     * <code>
     * do {
     *     $status = $client->getResponse($job->getJobHandle());
     *     echo 'Progress: ', $status->getPercentCompleteNumerator(), PHP_EOL;
     * }
     * while ($status->getRunningStatus() == 1);
     * </code>
     *
     * For non-background jobs, the server forwards any status update messages
     * (WORK_STATUS) issued by the worker to the client. For jobs that expected
     * to produce WORK_STATUS responses to the client, call this method in a
     * loop until the packet WORK_COMPLETE or something else is returned.
     * i.e:
     * <code>
     * do {
     *     $status = $client->getResponse() // Without an argument.
     *     echo
     * }
     * while ($status->getType() == Phearman::TYPE_WORK_STATUS);
     * </code>
     *
     * @access public
     * @param null|string $jobHandle
     * @return Phearman\Task
     */
    public function getStatus($jobHandle = null)
    {
        /* Assume we did a background job and we want to check the status of
         * that background job, in which case, we need to send a GET_STATUS
         * packet with the job handle. The server will respond with a
         * STATUS_RES packet. */
        if ($jobHandle != null) {
            $task = new GetStatus($jobHandle);
            $this->adapter->write($task);

            $this->log("> GET_STATUS {$jobHandle}.");
        }

        /* Read response from the server. If we're checking the status for a
         * non-background job, i.e: called this method without an argument,
         * then the server will forward the WORK_STATUS packets from the
         * worker. */
        $response = $this->adapter->read();

        /* Log if response is a WORK_STATUS packet. */
        if ($response->getType() == Phearman::TYPE_WORK_STATUS) {
            $this->log(
                "< WORK_STATUS {$response->getJobHandle()} "
              . "{$response->getPercentCompleteNumerator()}/"
              . "{$response->getPercentCompleteDenominator()}%.");

        /* Log if response is a STATUS_RES packet. */
        } elseif ($response->getType() == Phearman::TYPE_STATUS_RES) {
            $this->log(
                "< STATUS_RES {$response->getJobHandle()} "
              . "{$response->getKnownStatus()}:{$response->getRunningStatus()}:"
              . "{$response->getPercentCompleteNumerator()}/"
              . "{$response->getPercentCompleteDenominator()}%.");

        /* Log for any other type of response packet. */
        } else {
            $this->log("< {$response->getTypeName()}.");
        }

        return $response;
    }
}
