<?php

/**
 * Implements the WORK_STATUS client response packet.
 *
 * This is sent to update the server (and any listening clients) of the status
 * of a running job. The worker should send these periodically for long running
 * jobs to update the percentage complete. The job server should store this
 * information so a client who issued a background command may retrieve it
 * later with a GET_STATUS request.
 *
 * Arguments:
 * - NULL byte terminated job handle.
 * - NULL byte terminated percent complete numerator.
 * - Percent complete denominator.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

class WorkStatus extends Task
{
    protected $jobHandle;
    protected $percentCompleteNumerator;
    protected $percentCompleteDenominator;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_WORK_STATUS;
    }

    public function setFromResponse($packet)
    {
        $packet = explode("\0", $packet);
        $this->jobHandle = $packet[0];
        $this->percentCompleteNumerator   = $packet[1];
        $this->percentCompleteDenominator = $packet[2];
    }
}
