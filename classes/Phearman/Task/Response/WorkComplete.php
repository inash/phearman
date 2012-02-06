<?php

/**
 * Implements the WORK_COMPLETE packet.
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
 * @subpackage Task\Response
 */

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

class WorkComplete extends Task
{
    protected $jobHandle;
    protected $workload;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_WORK_COMPLETE;
    }

    public function setFromResponse($packet)
    {
        $packet = explode("\0", $packet);
        $this->jobHandle = $packet[0];
        $this->workload  = $packet[1];
    }
}
