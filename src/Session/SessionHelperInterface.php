<?php

namespace TailgateWeb\Session;

use ArrayAccess;
use Countable;
use IteratorAggregate;

interface SessionHelperInterface extends ArrayAccess, Countable, IteratorAggregate
{

}