<?php
/**
 *
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Sylvain de Vathaire, Julien Issler
 * @contributor Florian Lonqueu-Brochard
 *
 * @copyright  2001-2005 CopixTeam, 2005-2020 Laurent Jouanneau, 2009 Julien Issler, 2012 Florian Lonqueu-Brochard
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\Mysqli;

use Jelix\Database\AbstractConnection;
use Jelix\Database\Exception;
use Psr\Log\LoggerInterface;

/**
 */
class Connection extends AbstractConnection
{
    protected $_charsets = array('UTF-8' => 'utf8', 'ISO-8859-1' => 'latin1');

    public function __construct($profile, LoggerInterface $logger = null)
    {
        // Because of the use of '@', we must test the existence of Mysql
        // else we cou
        if (!function_exists('mysqli_connect')) {
            throw new Exception('Mysqli extension is not installed in PHP', 405);
        }
        parent::__construct($profile, $logger);
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
        return '`'.$fieldName.'`';
    }

    /**
     * begin a transaction.
     */
    public function beginTransaction()
    {
        $this->_connection->begin_transaction();
        $this->_autoCommitNotify(false);
    }

    /**
     * Commit since the last begin.
     */
    public function commit()
    {
        $this->_connection->commit();
        $this->_autoCommitNotify(true);
    }

    /**
     * Rollback since the last begin.
     */
    public function rollback()
    {
        $this->_connection->rollback();
        $this->_autoCommitNotify(true);
    }

    /**
     * @param mixed $query
     */
    public function prepare($query)
    {
        list($newQuery, $parameterNames) = $this->findParameters($query, '?');
        $res = $this->_connection->prepare($newQuery);
        if ($res) {
            $rs = new ResultSet(null, $res, $parameterNames);
        } else {
            throw new Exception('invalid query: '.$this->_connection->error.'('.$query.')', 403);
        }

        return $rs;
    }

    public function errorInfo()
    {
        return array('HY000', $this->_connection->errno, $this->_connection->error);
    }

    public function errorCode()
    {
        return $this->_connection->errno;
    }

    protected function _connect()
    {
        $host = ($this->_profile['persistent']) ? 'p:'.$this->_profile['host'] : $this->_profile['host'];
        if (isset($this->_profile['ssl']) && $this->_profile['ssl']) {
            $cnx = mysqli_init();
            if (!$cnx) {
                throw new Exception('Error during the connection on '.$this->_profile['host'], 402);
            }
            mysqli_ssl_set(
                $cnx,
                (isset($this->_profile['ssl_key_pem']) ? $this->_profile['ssl_key_pem'] : null),
                (isset($this->_profile['ssl_cert_pem']) ? $this->_profile['ssl_cert_pem'] : null),
                (isset($this->_profile['ssl_cacert_pem']) ? $this->_profile['ssl_cacert_pem'] : null),
                null,
                null
            );
            if (!mysqli_real_connect(
                $cnx,
                $host,
                $this->_profile['user'],
                $this->_profile['password'],
                $this->_profile['database']
            )) {
                throw new Exception('Error during the connection on '.$this->_profile['host'], 402);
            }
        } else {
            $cnx = @new \mysqli($host, $this->_profile['user'], $this->_profile['password'], $this->_profile['database']);
        }
        if ($cnx->connect_errno) {
            throw new Exception('Error during the connection on '.$this->_profile['host'], 402);
        }

        if ($this->_profile['force_encoding'] == true
            && isset($this->_charsets[$this->_profile['charset']])
        ) {
            $cnx->set_charset($this->_charsets[$this->_profile['charset']]);
        }

        return $cnx;
    }

    protected function _disconnect()
    {
        return $this->_connection->close();
    }

    protected function _doQuery($query)
    {
        if ($qI = $this->_connection->query($query)) {
            return new ResultSet($qI);
        }

        throw new Exception('invalid query: '.$this->_connection->error.'('.$query.')', 403);
    }

    protected function _doExec($query)
    {
        if ($qI = $this->_connection->query($query)) {
            return $this->_connection->affected_rows;
        }

        throw new Exception('invalid query: '.$this->_connection->error.'('.$query.')', 403);
    }

    protected function _doLimitQuery($queryString, $offset, $number)
    {
        $queryString .= ' LIMIT '.$offset.','.$number;
        $this->lastQuery = $queryString;

        return $this->_doQuery($queryString);
    }

    public function lastInsertId($fromSequence = '')
    {
        return $this->_connection->insert_id;
    }

    /**
     * tell mysql to be autocommit or not.
     *
     * @param bool $state the state of the autocommit value
     */
    protected function _autoCommitNotify($state)
    {
        $this->_connection->autocommit($state);
    }

    /**
     * @param mixed $text
     * @param mixed $binary
     *
     * @return string escaped text or binary string
     */
    protected function _quote($text, $binary)
    {
        return $this->_connection->real_escape_string($text);
    }

    /**
     * @param int $id the attribut id
     *
     * @return string the attribute value
     *
     * @see \PDO::getAttribute()
     */
    public function getAttribute($id)
    {
        switch ($id) {
            case self::ATTR_CLIENT_VERSION:
                return $this->_connection->get_client_info();
            case self::ATTR_SERVER_VERSION:
                return $this->_connection->server_info;

                break;
            case self::ATTR_SERVER_INFO:
                return $this->_connection->host_info;
        }

        return '';
    }

    /**
     * @param int    $id    the attribute id
     * @param string $value the attribute value
     *
     * @see \PDO::setAttribute()
     */
    public function setAttribute($id, $value)
    {
    }

    /**
     * Execute several sql queries.
     *
     * @param mixed $queries
     */
    public function execMulti($queries)
    {
        $query_res = $this->_connection->multi_query($queries);
        while ($this->_connection->more_results()) {
            $this->_connection->next_result();
            if ($discard = $this->_connection->store_result()) {
                $discard->free();
            }
        }

        return $query_res;
    }

    protected function _getSchema()
    {
        throw new Exception('not implemented');
    }
}
