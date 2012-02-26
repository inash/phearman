<?php

namespace Phearman;
use Phearman\Exception;
use Phearman\Connection;
use Phearman\Task\Request\CanDo;
use Phearman\Task\Request\GrabJob;
use Phearman\Task\Request\PreSleep;
use Phearman\Task\Request\WorkComplete;
use Phearman\Task\Request\EchoReq;

/**
 * Main Phearman Worker class. This is the primary class to setup Gearman
 * workers, attach functions to it and set it to work under a run loop.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @package Phearman
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class Worker extends Connection
{
    /**
     * Holds the registered functions with the worker.
     *
     * It is used to submit the workers capabilities to server and to lookup
     * which function to execute based on the requested job assignment
     * capability.
     *
     * @access private
     * @var array
     */
    private $functions = array();

    /**
     * Register a capability with a given function with the server.
     *
     * Below is the types of registration accepted by this method.
     *
     * <code>
     * $worker->register('functionName');
     * $worker->register('jobName', 'functionName');
     *
     * $worker->register('MyClass');
     * $worker->register('MyObject', 'method');
     * $worker->register('MyClass::jobStatus');
     * $worker->register('jobName', 'MyObject::jobStatus');
     * $worker->register(array('ClassName', 'methodName'));
     * $worker->register('jobName', array('ClassName', 'methodName'));
     *
     * $worker->register($object);
     * $worker->register($object, 'method');
     * $worker->register(array($object, 'methodName'));
     * $worker->register('jobName', array($object, 'methodName'));
     * $worker->register('jobName', function($job, $worker) { // Closure; });
     * </code>
     *
     * @access public
     * @param string $jobName
     * @param string|array|closure $functionName
     * @throws Phearman\Exception
     */
    public function register($jobName, $functionName = null)
    {
        $typeJobName = gettype($jobName);
        $typeFunName = gettype($functionName);

        $classJobName = $classFunName = null;
        if ($typeJobName == 'string' && class_exists($jobName, false))
            $classJobName = $jobName;
        elseif ($typeJobName == 'object')
            $classJobName = get_class($jobName);

        if ($typeFunName == 'object')
            $classFunName = get_class($functionName);

        $case = $typeJobName . $typeFunName;

        switch ($case) {

            /* When we're registering a function with the given jobName, or a
             * static class method with a specific jobName, or if we want to
             * register a whole class with all it's static methods. */
            case 'stringNULL':

                /* Check if it's static method call. */
                if (strstr($jobName, '::')) {
                    $this->registerStaticMethod($jobName);
                    return;
                }

                /* Check if the given jobName is a Class, get all it's public
                 * methods and register them. */
                if (class_exists($jobName, false)) {
                    $class   = new \ReflectionClass($jobName);
                    $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

                    /* Check if there are any registerable methods in the class.
                     * Otherwise throw an exception. */
                    if (count($methods) == 0)
                        throw new Exception(
                            "There are no public methods in the class {$jobName} "
                          . 'that can be registered with the worker.');

                    foreach ($methods as $method) {
                        $this->functions[$method->name] = array(
                            $method->class, $method->name);
                    }

                    return;
                }

                /* Assuming this is a function name, check if the function
                 * exists. */
                if (!function_exists($jobName))
                    throw new Exception("Function {$jobName} does not exist.");

                $this->functions[$jobName] = $jobName;
                return;

            /* When we're registering a named function, or registering a static
             * class method with a specific jobName, or passing the name of a
             * class and it's static method as separate arguments. */
            case 'stringstring':

                /* Check if it's a static method call. */
                if (strstr($functionName, '::')) {
                    $this->registerStaticMethod($functionName, $jobName);
                    return;
                }

                /* Check if the jobName is a class, and that we want to register
                 * the second argument as it's static method call. */
                if (class_exists($jobName, false)) {
                    if (!method_exists($jobName, $functionName))
                        throw new Exception(
                            "Class method {$jobName}::{$functionName} does not "
                          . "exists.");

                    $this->functions[$functionName] = array($jobName, $functionName);
                    return;
                }

                /* Check if we need to register a plain old function with the
                 * given jobName. */
                if (!function_exists($functionName))
                    throw new Exception("Function {$functionName} does not exists.");

                $this->functions[$jobName] = $functionName;
                return;

            /* When we're registering (given an array) a static method call or
             * a direct method call. */
            case 'arrayNULL':
                $this->registerArray($jobName);
                return;

            /* When we're registering a whole object's public methods. */
            case 'objectNULL':
                $class   = new \ReflectionClass(get_class($jobName));
                $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

                /* If there are no methods that can be registerd, throw an
                 * exception. */
                if (count($methods) == 0)
                    throw new Exception(
                        'No methods to register with the given Object.');

                foreach ($methods as $method)
                    $this->functions[$method->name] = array($jobName, $method->name);

                return;

            /* When we're registering an object method by it's given name. */
            case 'objectstring':

                /* Check if the method exists and is callable. */
                if (!method_exists($jobName, $functionName)
                && !is_callable(array($jobName, $functionName)))
                    throw new Exception(
                        "The method {$functionName} does not exist, or is not "
                      . 'callable.');

                $this->functions[$functionName] = array($jobName, $functionName);
                return;

            /* When we're registering a named closure. :). */
            case 'stringobject':

                if (get_class($functionName) != 'Closure')
                    throw new Exception('The provided argument is not a closure.');

                $this->functions[$jobName] = $functionName;
                return;

            /* When we're registering a named function given a static method
             * call or direct method call compound array. */
            case 'stringarray':
                $this->registerArray($functionName, $jobName);
                return;
        }
    }

    public function isRegistered($functionName)
    {
        if (isset($this->functions[$functionName])) return true;
        return false;
    }

    public function getRegisteredFunctionNames()
    {
        return array_keys($this->functions);
    }

    /**
     * Set the worker to work.
     *
     * This method connects to the worker to a German server, submits it's
     * capabilities and goes into the GRAB_JOB loop. When a job is returned
     * the function associated with the job will be executed.
     *
     * @access public
     */
    public function work()
    {
        $this->log('+ Starting work.');

        /* Submit capabilities to the server. */
        foreach ($this->functions as $jobName => $functionName) {

            /* Prepare can do packet to send to the server. */
            $task = new CanDo($jobName);

            /* Send the task to the server. */
            $this->log("+ Registering capability {$jobName} with server.");
            $this->adapter->write($task);
        }

        /* Main loop to check for jobs. */
        while (true) {

            /* Now send a grabJob request and wait for a response. */
            $this->log('> GRAB_JOB.');
            $this->adapter->write(new GrabJob());

            while (true) {

                /* Read response from server. */
                $job = $this->adapter->read();
                $this->log("< {$job->getTypeName()}.");

                /* Sleep if the response is a no job packet. */
                if ($job->getType() == Phearman::TYPE_NO_JOB) {
                    $this->log('> PRE_SLEEP.');
                    $task = new PreSleep();
                    $this->adapter->write($task);
                }

                /* Check if response is a job assignment */
                elseif ($job->getType() == Phearman::TYPE_JOB_ASSIGN) {
                    $this->log(sprintf(
                        '* %s %s.', $job->getFunctionName(), $job->getJobHandle()));

                    /* Call the function and do the job. */
                    $functionName = $job->getFunctionName();
                    $output = call_user_func($this->functions[$functionName], $job, $this);

                    /* Create a work complete request from the work. */
                    $task = new WorkComplete($job->getJobHandle());
                    $task->setWorkload($output);
                    $this->adapter->write($task);
                    $this->log("> WORK_COMPLETE {$job->getJobHandle()}.");
                }

                /* Check if response is a wake up call (NOOP) from the server.
                 * If so, continue the parent loop process, starting by grabbing
                 * a new job from the server. */
                if (in_array($job->getType(), array(
                Phearman::TYPE_NOOP,
                Phearman::TYPE_JOB_ASSIGN)))
                    continue 2;
            }
        }
    }

    /**
     * Update the status of a running job.
     *
     * This method sends the WORK_STATUS request to the server given a job
     * handle, percent complete numerator and percent complete denominator.
     *
     * While executing long running jobs, it might be necessary to send the
     * progress to the client. As the work progresses this method can be
     * called through the second argument passed to the job function (which is
     * the worker itself) providing the job handle and the progress details.
     *
     * e.g:
     * <code>
     * function exampleJob($job, $worker) {
     *     $jobHandle = $job->getJobHandle();
     *     $worker->updateStatus($jobHandle, 33, 100);
     *     $worker->updateStatus($jobHandle, 66, 100);
     *     $worker->updateStatus($jobHandle, 100, 100);
     *     return 'Job completed successfully.';
     * }
     * </code>
     *
     * @access public
     * @param string $jobHandle
     * @param integer $percentNum
     * @param integer $percentDen
     */
    public function updateStatus($jobHandle, $percentNum, $percentDen)
    {
        $task = new Task\Request\WorkStatus(
            $jobHandle, $percentNum, $percentDen);
        $this->adapter->write($task);
        $this->log("> WORK_STATUS {$jobHandle} {$percentNum}/{$percentDen}.");
    }

    /**
     * Register a static method call as a worker function.
     *
     * The below code will check if the class MyClass exists, otherwise it will
     * throw an error. It is required that the class should already be declared
     * prior to calling this method, and it will not be autoloaded when checking
     * for it as well.
     *
     * The second argument is optional. If it is given, the function will be
     * registered with the given jobName (eg: doSomething), otherwise it will be
     * registered with the methodName qualifier part (eg: myMethod).
     *
     * <code>
     * $this->registerStaticMethod('MyClass::myMethod', 'doSomething');
     * </code>
     *
     * @access private
     * @param string $methodName
     * @param null|string $jobName
     * @throws Phearman\Exception
     */
    private function registerStaticMethod($methodName, $jobName = null)
    {
        list($className, $method) = explode('::', $methodName);
        $method = str_replace(array('(', ')'), '', $method);

        /* Throw exception the class name could not be found. */
        if (!class_existS($className, false))
            throw new Exception("Class {$className} does not exist.");

        /* Throw exception if the class method could not be found. */
        if (!method_exists($className, $method))
            throw new Exception("Class method {$methodName} does not exist.");

        $jobName = ($jobName != null) ? $jobName : $method;
        $this->functions[$jobName] = array($className, $method);
    }

    private function registerArray(Array $array, $jobName = null)
    {
        /* Throw exception if 2 elements were not provided. */
        if (count($array) != 2)
            throw new Exception(
                'To register an Array it has to have 2 elements, Class/Object '
              . 'and a Method name.');

        /* Check if the first element is a string and assume it's a Class. */
        if (is_string($array[0])) {
            if (!class_exists($array[0], false))
                throw new Exception("Class {$array[0]} does not exists.");

            if (!method_exists($array[0], $array[1]))
                throw new Exception(
                    "Class method {$array[0]}::{$array[1]} does not exist.");
        }

        /* Check if the second element is an object. */
        if (is_object($array[0])) {
            if (!method_exists($array[0], $array[1]))
                throw new Exception("Object method {$array[1]} does not exist.");
        }

        $jobName = ($jobName != null) ? $jobName : $array[1];
        $this->functions[$jobName] = $array;
        return;
    }
}
