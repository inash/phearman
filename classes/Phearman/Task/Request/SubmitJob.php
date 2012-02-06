<?php

/**
 * Implements the SUBMIT_JOB, SUBMIT_JOB_BG, SUBMIT_JOB_HIGH,
 * SUBMIT_JOB_HIGH_BG, SUBMIT_JOB_LOW, SUBMIT_JOB_LOW_BG packets.
 *
 * A client issues one of these when a job needs to be run. The server will then
 * assign a job handle and respond with a JOB_CREATED packet.
 *
 * If one of the BG versions is used, the client is not updated with status or
 * notified when the job has completed (it is detached).
 *
 * The Gearman job server queue is implemented with three levels: normal, high,
 * and low. Jobs submitted with one of the HIGH versions always take precedence,
 * and jobs submitted with the normal versions take precedence over the LOW
 * versions.
 *
 * Arguments:
 * - NULL byte terminated function name.
 * - NULL byte terminated unique ID.
 * - Opaque data that is given to the function as an argument.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 */

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;
use Phearman\Exception;

class SubmitJob extends Task
{
    protected $functionName;
    protected $uniqueId;
    protected $workload;

    public function __construct($uniqueId = null, $type = Phearman::TYPE_SUBMIT_JOB)
    {
        /* Validate job type and priority argument values. */
        if (!in_array($type, array(
            Phearman::TYPE_SUBMIT_JOB,
            Phearman::TYPE_SUBMIT_JOB_HIGH,
            Phearman::TYPE_SUBMIT_JOB_LOW,
            Phearman::TYPE_SUBMIT_JOB_BG,
            Phearman::TYPE_SUBMIT_JOB_HIGH_BG,
            Phearman::TYPE_SUBMIT_JOB_LOW_BG)
        )) {
            throw new Exception(
                'Invalid job type. Check the different Submit Job types.');
        }

        /* Set packet code, submit job type and uniqueId. */
        $this->code     = Phearman::CODE_REQUEST;
        $this->type     = $type;
        $this->uniqueId = $uniqueId;
    }

    protected function getDataPart()
    {
        return array(
            $this->functionName,
            $this->uniqueId,
            $this->workload);
    }
}
