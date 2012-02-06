<?php

/**
 * Implements the STATUS_RES packet.
 *
 * This is sent in response to a GET_STATUS request. This is used by clients
 * that have submitted a job with SUBMIT_JOB_BG to see if the job has been
 * completed, and if not, to get the percentage complete.
 *
 * Arguments:
 * - NULL byte terminated job handle.
 * - NULL byte terminated known status, this is 0 (false) or 1 (true).
 * - NULL byte terminated running status, this is 0 (false) or 1 (true).
 * - NULL byte terminated percent complete numerator.
 * - Percent complete denominator.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Task\Response
 */

namespace Phearman\Task\Response;
use Phearman\Phearman;
use Phearman\Task;

class StatusRes extends Task
{
    protected $jobHandle;
    protected $knownStatus;
    protected $runningStatus;
    protected $percentCompleteNumerator;
    protected $percentCompleteDenominator;

    public function __construct()
    {
        $this->code = Phearman::CODE_RESPONSE;
        $this->type = Phearman::TYPE_JOB_CREATED;
    }

    public function setFromResponse($packet)
    {
        $packet = explode("\0", $packet);
        $this->jobHandle     = $packet[0];
        $this->knownStatus   = $packet[1];
        $this->runningStatus = $packet[2];
        $this->percentCompleteNumerator   = $packet[3];
        $this->percentCompleteDenominator = $packet[4];
    }
}
