<?php
/**
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2017 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */
namespace Opulence\Tests\Databases\Mocks;

use Opulence\Databases\IConnection;
use Opulence\Databases\IDriver;
use Opulence\Databases\Server as BaseServer;

/**
 * Mocks the driver class for use in testing
 */
class Driver implements IDriver
{
    /**
     * @inheritdoc
     */
    public function connect(BaseServer $server, array $connectionOptions = [], array $driverOptions = []) : IConnection
    {
        return new Connection($server);
    }
} 