<?php

namespace Phearman;

define('PHEARMAN_PATH', realpath(dirname(__FILE__)));

/**
 * Main Phearman class houses the different library constants used through out
 * library, initializing and autoloading.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class Phearman
{
    const VERSION = '0.1';

    /* 4 byte magic codes - This is either "\0REQ" forrequests or "\0RES"
     * for responses. */
    const CODE_REQUEST  = 'REQ';
    const CODE_RESPONSE = 'RES';

    /* 4 byte types - A big-endian (network-order) integer containing an
     * enumerated packet type. Possible values are: */
    const TYPE_CAN_DO             = 1;
    const TYPE_CANT_DO            = 2;
    const TYPE_RESET_ABILITIES    = 3;
    const TYPE_PRE_SLEEP          = 4;
    const TYPE_NOOP               = 6;
    const TYPE_SUBMIT_JOB         = 7;
    const TYPE_JOB_CREATED        = 8;
    const TYPE_GRAB_JOB           = 9;
    const TYPE_NO_JOB             = 10;

    const TYPE_JOB_ASSIGN         = 11;
    const TYPE_WORK_STATUS        = 12;
    const TYPE_WORK_COMPLETE      = 13;
    const TYPE_WORK_FAIL          = 14;
    const TYPE_GET_STATUS         = 15;
    const TYPE_ECHO_REQ           = 16;
    const TYPE_ECHO_RES           = 17;
    const TYPE_SUBMIT_JOB_BG      = 18;
    const TYPE_ERROR              = 19;
    const TYPE_STATUS_RES         = 20;

    const TYPE_SUBMIT_JOB_HIGH    = 21;
    const TYPE_SET_CLIENT_ID      = 22;
    const TYPE_CAN_DO_TIMEOUT     = 23;
    const TYPE_ALL_YOURS          = 24;
    const TYPE_WORK_EXCEPTION     = 25;
    const TYPE_OPTION_REQ         = 26;
    const TYPE_OPTION_RES         = 27;
    const TYPE_WORK_DATA          = 28;
    const TYPE_WORK_WARNING       = 29;
    const TYPE_GRAB_JOB_UNIQ      = 30;

    const TYPE_JOB_ASSIGN_UNIQ    = 31;
    const TYPE_SUBMIT_JOB_HIGH_BG = 32;
    const TYPE_SUBMIT_JOB_LOW     = 33;
    const TYPE_SUBMIT_JOB_LOW_BG  = 34;
    const TYPE_SUBMIT_JOB_SCHED   = 35;
    const TYPE_SUBMIT_JOB_EPOCH   = 36;

    public static function registerAutoloader()
    {
        spl_autoload_register(__NAMESPACE__ . '\Phearman::autoload');
    }

    public static function autoload($name)
    {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $name);
        $file = PHEARMAN_PATH . DIRECTORY_SEPARATOR . $file . '.php';

        /* Require file. */
        require_once $file;
    }
}
