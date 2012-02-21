<?php

/**
 * Implements the JOB_CREATED packet.
 *
 * This is sent in response to one of the SUBMIT_JOB* packets. It signifies to
 * the client that a server successfully received the job and queued it to be
 * run by a worker.
 *
 * Arguments:
 * - Job handle assigned by server.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

class JobCreated extends Task
{
    protected $jobHandle;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_JOB_CREATED;
    }

    public function setFromResponse($packet)
    {
        $this->jobHandle = $packet;
    }
}
