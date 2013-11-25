<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
namespace AlphaRPC\Common\Protocol\Message;

use AlphaRPC\Common\Socket\Message;

/**
 * Class ErrorMessageAbstract
 *
 * @package AlphaRPC
 * @subpackage Common
 */
abstract class ErrorMessageAbstract extends MessageAbstract implements ErrorMessageInterface
{
}
