<?php

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;
use Phearman\Exception;

/**
 * Implements the PRE_SLEEP worker request packet.
 *
 * This is sent to notify the server that the worker is about to sleep, and that
 * it should be woken up with a NOOP packet if a job comes in for a function
 * that worker is able to perform.
 *
 * Arguments:
 * - None.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class PreSleep extends Task
{
    public function __construct()
    {
        /* Set packet code and type. */
        $this->code = Phearman::CODE_REQUEST;
        $this->type = Phearman::TYPE_PRE_SLEEP;
    }
}
