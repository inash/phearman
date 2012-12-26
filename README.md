Introduction
============

Phearman is Gearman client/worker class library written in pure PHP.
The goals of this project is to develop a modular library for use with PHP 5.3.
It is inspired from Pheanstalk PHP client library for Beanstalkd and tries to
adhere to the pecl/gearman PHP extension interface and standard.

[API Documentation](http://inashzubair.com/phearman/api/)

© Inash Zubair


Example
-------

```php
<?php

require_once 'phearman_init.php';

use Phearman\Client;

/* Client echo request. */
$client   = new Client();
$response = $client->echoRequest('Hello Gearman');

echo $response->getWorkload();
```
