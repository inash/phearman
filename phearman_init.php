<?php

/**
 * Base exception class for exceptions thrown from the Phearman class
 * library.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman;

$phearmanClassRoot = dirname(__FILE__) . '/classes';
require_once $phearmanClassRoot . '/Phearman.php';

Phearman::registerAutoloader();
