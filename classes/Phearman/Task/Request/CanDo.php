<?php

/**
 * Implements the CAN_DO worker request packet.
 *
 * This is sent to notify the server that the worker is able to perform the
 * given function. The worker is then put on a list to be woken up whenever the
 * job server receives a job for that function.
 *
 * Arguments:
 * - Function name.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Request
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;
use Phearman\Exception;

class CanDo extends Task
{
    protected $functionName;

    public function __construct($functionName)
    {
        /* Set packet code, submit job type and uniqueId. */
        $this->code         = Phearman::CODE_REQUEST;
        $this->type         = Phearman::TYPE_CAN_DO;
        $this->functionName = $functionName;
    }

    protected function getDataPart()
    {
        return $this->functionName;
    }
}
