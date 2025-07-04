<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
*  All rights reserved
*
*  http://sabre.io/baikal
*
*  This script is part of the Baïkal Server project. The Baïkal
*  Server project is free software; you can redistribute it
*  and/or modify it under the terms of the GNU General Public
*  License as published by the Free Software Foundation; either
*  version 2 of the License, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * dav.php
 * commit: bcaee23b317fcd508fc1e691e36724c27dadba86
 * release: 0.10.1
 */

use Symfony\Component\Yaml\Yaml;

ini_set("session.cookie_httponly", 1);
ini_set("display_errors", 0);
ini_set("log_errors", 1);

define("BAIKAL_CONTEXT", true);
define("PROJECT_CONTEXT_BASEURI", "/");

if (file_exists(getcwd() . "/Core")) {
    # Flat FTP mode
    define("PROJECT_PATH_ROOT", getcwd() . "/");    #./
} else {
    # Dedicated server mode
    define("PROJECT_PATH_ROOT", dirname(getcwd()) . "/");    #../
}

if (!file_exists(PROJECT_PATH_ROOT . 'vendor/')) {
    exit('<h1>Incomplete installation</h1><p>Ba&iuml;kal dependencies have not been installed. If you are a regular user, this means that you probably downloaded the wrong zip file.</p><p>To install the dependencies manually, execute "<strong>composer install</strong>" in the Ba&iuml;kal root folder.</p>');
}
require PROJECT_PATH_ROOT . 'vendor/autoload.php';

# Bootstrapping Flake
\Flake\Framework::bootstrap();

# Bootstrapping Baïkal
\Baikal\Framework::bootstrap();

try {
    $config = Yaml::parseFile(PROJECT_PATH_CONFIG . "baikal.yaml");
} catch (\Exception $e) {
    exit('<h1>Incomplete installation</h1><p>Ba&iuml;kal is missing its configuration file, or its configuration file is unreadable.');
}


# Custom
class CustomServer extends \Baikal\Core\Server
{
    public function getDavServer()
    {
        return $this->server;
    }
    function exception($e)
    {
        if ($e instanceof CustomForbidden) {
            return;
        }
        parent::exception($e);
    }
}
abstract class CustomResponse extends \Sabre\HTTP\Response
{
    public function setStatus($status) {}
    public function setBody($body) {}
}
class CustomResponse204 extends CustomResponse
{
    protected $status = 204;
    protected $statusText = 'No Content';
}
class CustomResponse201 extends CustomResponse
{
    protected $status = 201;
    protected $statusText = 'Created';
}
abstract class CustomForbidden extends \Sabre\DAV\Exception\Forbidden {}
class CustomForbidden204 extends CustomForbidden
{
    public function serialize(\Sabre\DAV\Server $server, \DOMElement $errorNode)
    {
        $server->httpResponse = new CustomResponse204;
    }
}
class CustomForbidden201 extends CustomForbidden
{
    public function serialize(\Sabre\DAV\Server $server, \DOMElement $errorNode)
    {
        $server->httpResponse = new CustomResponse201;
    }
}
$server = new CustomServer(
    $config['system']["cal_enabled"],
    $config['system']["card_enabled"],
    $config['system']["dav_auth_type"],
    $config['system']["auth_realm"],
    $GLOBALS['DB']->getPDO(),
    PROJECT_BASEURI . 'dav_ro.php/'
);
$davServer = $server->getDavServer();
$davServer->on('beforeUnbind', fn () => throw new CustomForbidden204, 50);
$davServer->on('beforeCreateFile', fn () => throw new CustomForbidden201, 50);
$davServer->on('beforeWriteContent', fn () => throw new CustomForbidden204, 50);
$server->start();
