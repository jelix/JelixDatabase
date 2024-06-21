<?php
/**
 * @author     Loic Mathaud
 * @contributor Laurent Jouanneau
 *
 * @copyright  2006 Loic Mathaud, 2007-2024 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\SQLite3;

use Jelix\Database\AbstractConnection;
use Jelix\Database\Exception;
use Jelix\Database\Schema\Sqlite\TableName;
use Jelix\Database\Schema\TableNameInterface;

/**
 */
class Connection extends AbstractConnection
{
    /**
     * @inheritDoc
     */
    public function __construct($profile)
    {
        if (!class_exists('SQLite3')) {
            throw new Exception('Sqlite3 extension is not installed in PHP', 405);
        }
        parent::__construct($profile);
    }

    /**
     * begin a transaction.
     */
    public function beginTransaction()
    {
        $this->_doExec('BEGIN');
    }

    /**
     * Commit since the last begin.
     */
    public function commit()
    {
        $this->_doExec('COMMIT');
    }

    /**
     * Rollback since the last BEGIN.
     */
    public function rollback()
    {
        $this->_doExec('ROLLBACK');
    }

    /**
     * @param mixed $query
     */
    public function prepare($query, $driverOptions = [])
    {
        $res = $this->_connection->prepare($query);
        if ($res) {
            $rs = new ResultSet(null, $res, $this);
        } else {
            throw new Exception('invalid query: '.$this->_connection->error.'('.$query.')', 403);
        }

        return $rs;
    }

    public function errorInfo()
    {
        return array($this->_connection->lastErrorCode(), $this->_connection->lastErrorMsg());
    }

    public function errorCode()
    {
        return $this->_connection->lastErrorCode();
    }

    protected function _connect()
    {
        $db = $this->_profile['database'];
        if ($this->_profile['filePathParser']) {
            $db = call_user_func_array($this->_profile['filePathParser'], array($db));
        }

        $sqlite = new \SQLite3($db);

        // Load extensions if needed
        if (isset($this->_profile['extensions'])) {
            $list = preg_split('/ *, */', $this->_profile['extensions']);
            foreach ($list as $ext) {
                try {
                    $sqlite->loadExtension($ext);
                } catch (\Exception $e) {
                    throw new Exception('sqlite3 connector: error while loading sqlite extension '.$ext);
                }
            }
        }

        // set timeout
        if (isset($this->_profile['busytimeout'])) {
            $timeout = intval($this->_profile['busytimeout']);
            if ($timeout) {
                $sqlite->busyTimeout($timeout);
            }
        }

        return $sqlite;
    }

    protected function _disconnect()
    {
        $this->_connection->close();
    }

    protected function _doQuery($query)
    {
        if ($qI = $this->_connection->query($query)) {
            return new ResultSet($qI, null, $this);
        }
        throw new Exception('invalid query: '.$this->_connection->lastErrorMsg().' ('.$query.')', 403);
    }

    protected function _doExec($query)
    {
        if ($this->_connection->exec($query)) {
            return $this->_connection->changes();
        }

        throw new Exception('invalid query: '.$this->_connection->lastErrorMsg().' ('.$query.')', 403);
    }

    protected function _doLimitQuery($queryString, $offset, $number)
    {
        $queryString .= ' LIMIT '.$offset.','.$number;
        $this->_lastQuery = $queryString;

        return $this->_doQuery($queryString);
    }

    public function lastInsertId($fromSequence = '')
    {
        return $this->_connection->lastInsertRowID();
    }

    /**
     * tell sqlite to be autocommit or not.
     *
     * @param bool $state the state of the autocommit value
     */
    protected function _autoCommitNotify($state)
    {
        $this->query('SET AUTOCOMMIT='.$state ? '1' : '0');
    }

    /**
     * @param mixed $text
     * @param mixed $binary
     *
     * @return string the text with non ascii char and quotes escaped
     */
    protected function _quote($text, $binary)
    {
        return $this->_connection->escapeString($text);
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
            case self::ATTR_SERVER_VERSION:
                $v = \SQLite3::version();

                return $v['versionString'];
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


    protected function _getSchema()
    {
        return new \Jelix\Database\Schema\Sqlite\Schema($this);
    }


    public function createTableName(string $name) : TableNameInterface
    {
        return new TableName($name, '', $this->getTablePrefix());
    }
}
