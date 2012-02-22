<?php

/**
 * The concrete Socket adapter impementation.
 *
 * Socket uses the default PHP fsockopen with internet domain socket.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @subpackage Adapter
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

namespace Phearman\Adapter;
use Phearman\Phearman;
use Phearman\Adapter;
use Phearman\Task;

final class Socket extends Adapter
{
    protected function connect()
    {
        $errorNumber = $errorString = null;

        /* Check if we're able to connect either of the provided servers upon
         * instantiation. */
        foreach ($this->hosts as $host) {
            $url = parse_url($host);
            $this->resource = @fsockopen(
                $url['host'], $url['port'], $errorNumber, $errorString);

            /* Break if connection succeeded with no error and no message. */
            if ($errorNumber == 0 && $errorString == '') {
                $this->connected = true;
                break;
            }
        }

        /* Handle connection errors. */
        if (!$this->resource) {
            $errorString = sprintf(
                'Could not connect to host %s:%s (last host in the chain). '
              . 'Reason: %s',
              $url['host'], $url['port'], $errorString);
            throw new Exception($errorString, $errorNumber);
        }
    }

    public function write($task)
    {
        /* Check if adapter is connected. If not, then make the connection
         * prior to proceeding. */
        if ($this->connected === false)
            $this->connect();

        // TODO
        fwrite($this->resource, $task);
    }

    public function read()
    {
        /* Check if adapter is connected. If not, then make the connection
         * prior to reading any data. */
        if ($this->connected === false)
            $this->connect();

        /* Error correction loop for junk in the data received. */
        do {
            $code = fread($this->resource, 4);
            $code = trim($code, "\0");
        } while ($code != 'RES');

        /* Unpack the header segment and assign them as type and length. */
        $header = fread($this->resource, 8);
        $header = unpack('N2', $header);
        $type   = $header[1];
        $length = $header[2];

        /* Create a task using the Task::factory method based on the received
         * packet type and code. Here code is used for the factory method
         * to map it conveniently to the specific class folders (Request/
         * Response). */
        $task = Task::factory($type, Phearman::CODE_RESPONSE);
        $task->setLength($length);

        /* Return the task if there is no data part associated with the
         * received message. */
        if ($length == 0) return $task;

        /* Break up data parts with the workload. */
        $packet = fread($this->resource, $length);
        $task->setFromResponse($packet);

        return $task;
    }

    public function close()
    {
        fclose($this->resource);
    }
}
