<?php

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

/**
 * Implements the JOB_ASSIGN worker response packet.
 *
 * This is given in response to a GRAB_JOB request to give the worker
 * information needed to run the job. All communication about the job (such as
 * status updates and completion response) should use the handle, and the worker
 * should run the given function with the argument.
 *
 * Arguments:
 * - NULL byte terminated job handle.
 * - NULL byte terminated function name.
 * - Opaque data that is given to the function as an argument.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class JobAssign extends Task
{
    protected $jobHandle;
    protected $functionName;
    protected $workload;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_JOB_ASSIGN;
    }

    public function setFromResponse($packet)
    {
        $packet = explode("\0", $packet);
        $this->jobHandle    = $packet[0];
        $this->functionName = $packet[1];
        $this->workload     = $packet[2];
    }
}
