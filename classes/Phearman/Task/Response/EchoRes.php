<?php

/**
 * Implements the ECHO_RES packet.
 *
 * This is sent in response to a ECHO_REQ request. The server doesn't look at
 * or modify the data argument, it just send it back.
 *
 * Arguments:
 * - Opaque data that is echoed back in response.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 */

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

class EchoRes extends Task
{
    protected $workload;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_ECHO_RES;
    }

    public function setFromResponse($packet)
    {
        $this->workload = $packet;
    }
}
