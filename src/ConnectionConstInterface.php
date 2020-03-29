<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2020 Laurent Jouanneau
 *
 * @see        http://jelix.org
 * @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\Database;

/**
 * Interface for constantes
 *
 * @package Jelix\Database
 *
 * @internal const are not into ConnectionInterface, else there is a conflict
 * with PDO in the PDO connector.
 */
interface ConnectionConstInterface
{
    const FETCH_OBJ = 5;
    const FETCH_CLASS = 8;
    const FETCH_INTO = 9;
    const ATTR_AUTOCOMMIT = 0;
    const ATTR_PREFETCH = 1;
    const ATTR_TIMEOUT = 2;
    const ATTR_ERRMODE = 3;
    const ATTR_SERVER_VERSION = 4;
    const ATTR_SERVER_INFO = 6;
    const ATTR_CLIENT_VERSION = 5;
    const ATTR_CONNECTION_STATUS = 7;
    const ATTR_CASE = 8;
    const ATTR_CURSOR = 10;
    const ATTR_ORACLE_NULLS = 11;
    const ATTR_PERSISTENT = 12;
    const ATTR_DRIVER_NAME = 16;
    const CURSOR_FWDONLY = 0;
    const CURSOR_SCROLL = 1;

}
