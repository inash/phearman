<?php

/**
 * Implements the GRAB_JOB worker request packet.
 *
 * This is sent to the server to request any available jobs on the queue.
 * The server will respond with NO_JOB or JOB_ASSIGN, depending on whether a
 * job is available.
 *
 * Arguments:
 * - None.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 */

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;
use Phearman\Exception;

class GrabJob extends Task
{
    public function __construct()
    {
        /* Set packet code, submit job type and uniqueId. */
        $this->code = Phearman::CODE_REQUEST;
        $this->type = Phearman::TYPE_GRAB_JOB;
    }
}
