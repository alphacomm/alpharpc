<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Jacob Kiers <jacob@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Common
 */

namespace AlphaRPC\Common\Protocol\Exception;

use InvalidArgumentException;

/**
 * Signals an invalid protocol version
 *
 * @package    AlphaRPC
 * @subpackage Common
 */
class UnknownMessageException extends InvalidArgumentException
{
}
