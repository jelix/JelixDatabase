<?php
/**
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Yannick Le Guédart
 * @contributor Laurent Raufaste
 * @contributor Julien Issler
 * @contributor Alexandre Zanelli
 *
 * @copyright  2001-2005 CopixTeam, 2005-2020 Laurent Jouanneau, 2007-2008 Laurent Raufaste, 2009 Julien Issler
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\Postgresql;

use Jelix\Database\AbstractConnection;
use Jelix\Database\Exception;
use Psr\Log\LoggerInterface;

/**
 */
class Connection extends AbstractConnection
{
    protected $_charsets = array('UTF-8' => 'UNICODE', 'ISO-8859-1' => 'LATIN1');

    public function __construct($profile, LoggerInterface $logger = null)
    {
        if (!function_exists('pg_connect')) {
            throw new Exception('Pgsql extension is not installed in PHP', 405);
        }
        parent::__construct($profile, $logger);
        if (isset($this->profile['single_transaction']) && ($this->profile['single_transaction'])) {
            $this->beginTransaction();
            $this->setAutoCommit(false);
        } else {
            $this->setAutoCommit(true);
        }
        if (version_compare(pg_parameter_status($this->_connection, 'server_version'), '9.0') > -1) {
            $this->_doExec('SET bytea_output = "escape"');
        }
    }

    /**
     * enclose the field name.
     *
     * @param string $fieldName the field name
     *
     * @return string the enclosed field name
     */
    public function encloseName($fieldName)
    {
        return '"'.$fieldName.'"';
    }

    public function __destruct()
    {
        if (isset($this->profile['single_transaction']) && ($this->profile['single_transaction'])) {
            $this->commit();
        }
        parent::__destruct();
    }

    public function beginTransaction()
    {
        return $this->_doExec('BEGIN');
    }

    public function commit()
    {
        return $this->_doExec('COMMIT');
    }

    public function rollback()
    {
        return $this->_doExec('ROLLBACK');
    }

    public function prepare($query)
    {
        list($newQuery, $parameterNames) = $this->findParameters($query, '$%');
        $id =  microtime();
        $res = pg_prepare($this->_connection, $id, $newQuery);
        if ($res) {
            $rs = new ResultSet($res, $id, $this->_connection, $parameterNames);
        } else {
            throw new Exception('invalid query: '.pg_last_error($this->_connection).'('.$query.')', 403);
        }

        return $rs;
    }

    public function errorInfo()
    {
        return array('HY000', pg_last_error($this->_connection), pg_last_error($this->_connection));
    }

    public function errorCode()
    {
        return pg_last_error($this->_connection);
    }

    protected function _connect()
    {
        $funcconnect = (isset($this->profile['persistent']) && $this->profile['persistent'] ? 'pg_pconnect' : 'pg_connect');

        $str = '';

        // Service is PostgreSQL way to store credentials in a file :
        // http://www.postgresql.org/docs/9.1/static/libpq-pgservice.html
        // If given, no need to add host, user, database, port and password
        if (isset($this->profile['service']) && $this->profile['service'] != '') {
            $str = 'service=\''.$this->profile['service'].'\''.$str;
        } else {
            // we do a distinction because if the host is given == TCP/IP connection else unix socket
            if ($this->profile['host'] != '') {
                $str = 'host=\''.$this->profile['host'].'\''.$str;
            }

            if (isset($this->profile['port'])) {
                $str .= ' port=\''.$this->profile['port'].'\'';
            }

            if ($this->profile['database'] != '') {
                $str .= ' dbname=\''.$this->profile['database'].'\'';
            }

            // we do isset instead of equality test against an empty string, to allow to specify
            // that we want to use configuration set in environment variables
            if (isset($this->profile['user'])) {
                $str .= ' user=\''.$this->profile['user'].'\'';
            }

            if (isset($this->profile['password'])) {
                $str .= ' password=\''.$this->profile['password'].'\'';
            }
        }

        if (isset($this->profile['timeout']) && $this->profile['timeout'] != '') {
            $str .= ' connect_timeout=\''.$this->profile['timeout'].'\'';
        }

        if (isset($this->profile['pg_options']) && $this->profile['pg_options'] != '') {
            $str .= ' options=\''.$this->profile['pg_options'].'\'';
        }

        // let's do the connection
        if ($cnx = @$funcconnect($str)) {
            if ($this->profile['force_encoding'] == true
               && isset($this->_charsets[$this->_profile['charset']])) {
                pg_set_client_encoding($cnx, $this->_charsets[$this->_profile['charset']]);
            }
        } else {
            if (isset($this->profile['service'])) {
                $uri = $this->profile['service'];
            } else {
                $uri = $this->profile['host'];
            }
            throw new Exception('Error during the connection on '.$uri, 402);
        }

        if (isset($this->profile['search_path']) && trim($this->profile['search_path']) != '') {
            $sql = 'SET search_path TO '.$this->profile['search_path'];
            if (!@pg_query($cnx, $sql)) {
                throw new Exception('invalid query: '.pg_last_error($cnx).'('.$sql.')', 403);
            }
        }

        return $cnx;
    }

    protected function _disconnect()
    {
        return pg_close($this->_connection);
    }

    protected function _doQuery($queryString)
    {
        if ($qI = @pg_query($this->_connection, $queryString)) {
            $rs = new ResultSet($qI);
            $rs->_connector = $this;
        } else {
            throw new Exception('invalid query: '.pg_last_error($this->_connection).'('.$queryString.')', 403);
        }

        return $rs;
    }

    protected function _doExec($query)
    {
        if ($rs = $this->_doQuery($query)) {
            return pg_affected_rows($rs->id());
        }

        return 0;
    }

    protected function _doLimitQuery($queryString, $offset, $number)
    {
        if ($number < 0) {
            $number = 'ALL';
        }
        $queryString .= ' LIMIT '.$number.' OFFSET '.$offset;
        $this->lastQuery = $queryString;

        return $this->_doQuery($queryString);
    }

    public function lastInsertId($seqname = '')
    {
        if ($seqname == '') {
            trigger_error(get_class($this).'::lastInstertId invalide sequence name', E_USER_WARNING);

            return false;
        }
        $cur = $this->query("select currval('${seqname}') as id");
        if ($cur) {
            $res = $cur->fetch();
            if ($res) {
                return $res->id;
            }

            return false;
        }
        trigger_error(get_class($this).'::lastInstertId invalide sequence name', E_USER_WARNING);

        return false;
    }

    protected function _autoCommitNotify($state)
    {
        if (version_compare(pg_parameter_status($this->_connection, 'server_version'), '7.4') < 0) {
            $this->_doExec('SET AUTOCOMMIT TO '.($state ? 'ON' : 'OFF'));
        }
    }

    protected function _quote($text, $binary)
    {
        if ($binary) {
            return pg_escape_bytea($this->_connection, $text);
        }

        return pg_escape_string($this->_connection, $text);
    }

    /**
     * @param integer $id the attribute id
     *
     * @return string the attribute value
     *
     * @see \PDO::getAttribute()
     */
    public function getAttribute($id)
    {
        switch ($id) {
            case self::ATTR_CLIENT_VERSION:
                $v = pg_version($this->_connection);
                return (array_key_exists('client', $v) ? $v['client'] : '');
            case self::ATTR_SERVER_VERSION:
                return pg_parameter_status($this->_connection, 'server_version');

                break;
        }

        return '';
    }

    /**
     * @param int    $id    the attribut id
     * @param string $value the attribute value
     *
     * @see \PDO::setAttribute()
     */
    public function setAttribute($id, $value)
    {
    }

    protected $serverVersion = 0;

    public function getServerMajorVersion()
    {
        if ($this->serverVersion === 0) {
            $version = $this->getAttribute($this::ATTR_SERVER_VERSION);
            if ($version != '') {
                $version = explode('.', $version);
                $this->serverVersion = intval($version[0]);
            }
        }
        return $this->serverVersion;
    }


    protected function _getSchema()
    {
        throw new Exception('not implemented');
    }
}
