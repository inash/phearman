<?php

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;

/**
 * Implements the ECHO_REQ packet.
 *
 * When a job server receives this request, it
 * simply generates a ECHO_RES packet with the data. This is primarily used for
 * testing or debugging.
 *
 * Arguments:
 * - Opaque data that is echoed back in response.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class EchoReq extends Task
{
    public function __construct($workload)
    {
        $this->code     = Phearman::CODE_REQUEST;
        $this->type     = Phearman::TYPE_ECHO_REQ;
        $this->workload = $workload;
    }

    protected function getDataPart()
    {
        return $this->workload;
    }
}
