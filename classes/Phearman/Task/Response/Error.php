<?php

/**
 * Implements the ERROR response packet.
 *
 * This is sent whenever the server encounters an error and needs to notify
 * a client or worker.
 *
 * Arguments:
 * - NULL byte terminated error code string.
 * - Error text.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

class Error extends Task
{
    protected $errorCode;
    protected $errorText;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_JOB_CREATED;
    }

    public function setFromResponse($packet)
    {
        $packet = explode("\0", $packet);
        $this->errorCode = $packet[0];
        $this->errorText = $packet[1];
    }
}
