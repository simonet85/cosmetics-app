<?php

namespace App\Exceptions;

use RuntimeException;

/** A problem the customer can understand and act on; its message is shown as is. */
class CheckoutException extends RuntimeException {}
