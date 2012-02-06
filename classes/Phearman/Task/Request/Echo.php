<?php

/**
 * Implements the ECHO_REQ packet. When a job server receives this request, it
 * simply generates a ECHO_RES packet with the data. This is primarily used for
 * testing or debugging.
 *
 * Arguments:
 * - Opaque data that is echoed back in response.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 */

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;

class Echo extends Task
{
    public function __construct($workload)
    {
        $this->_code     = Phearman::CODE_REQUEST;
        $this->_type     = Phearman::TYPE_ECHO_REQ;
        $this->_workload = $workload;
    }
}
