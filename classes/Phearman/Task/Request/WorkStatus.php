<?php

namespace Phearman\Task\Request;
use Phearman\Phearman;
use Phearman\Task;

/**
 * Implements the WORK_STATUS worker request packet.
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
 * @subpackage Task\Request
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class WorkStatus extends Task
{
    protected $jobHandle;
    protected $percentCompleteNumerator;
    protected $percentCompleteDenominator;

    public function __construct($jobHandle, $percentNum, $percentDen)
    {
        $this->code = Phearman::CODE_REQUEST;
        $this->type = Phearman::TYPE_WORK_STATUS;
        $this->jobHandle = $jobHandle;
        $this->percentCompleteNumerator   = $percentNum;
        $this->percentCompleteDenominator = $percentDen;
    }

    public function getDataPart()
    {
        return array(
            $this->jobHandle,
            $this->percentCompleteNumerator,
            $this->percentCompleteDenominator);
    }
}
