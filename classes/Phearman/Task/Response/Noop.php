<?php

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

/**
 * Implements the NOOP worker response packet.
 *
 * This is used to wake up a sleeping worker so that it may grab a pending job.
 *
 * Arguments:
 * - None.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class Noop extends Task
{
    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_NOOP;
    }
}
