<?php

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;
use Phearman\Exception;

/**
 * Implements the WORK_COMPLETE worker request packet.
 *
 * This is to notify the server (and any listening clients) that the job
 * completed successfully.
 *
 * Arguments:
 * - NULL byte terminated job handle.
 * - Opaque data that is returned to the client as a response.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class WorkComplete extends Task
{
    protected $jobHandle;
    protected $workload;

    public function __construct($jobHandle)
    {
        /* Set packet code, type and job handle. */
        $this->code      = Phearman::CODE_REQUEST;
        $this->type      = Phearman::TYPE_WORK_COMPLETE;
        $this->jobHandle = $jobHandle;
    }

    public function setWorkload($workload)
    {
        $this->workload = $workload;
    }

    protected function getDataPart()
    {
        return array(
            $this->jobHandle,
            $this->workload);
    }
}
