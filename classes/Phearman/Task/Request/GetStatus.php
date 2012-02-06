<?php

/**
 * Implements the GET_STATUS packet.
 *
 * A client issues this to get status information for a submitted job.
 *
 * Arguments:
 * - Job handle that was given in JOB_CREATED packet.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 */

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;

class GetStatus extends Task
{
    protected $jobHandle;

    public function __construct()
    {
        $this->code = Phearman::CODE_REQUEST;
        $this->type = Phearman::TYPE_GET_STATUS;
    }

    protected function getDataPart()
    {
        return array($this->jobHandle);
    }
}
