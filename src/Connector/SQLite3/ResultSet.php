<?php
/**
 * @author     Loic Mathaud
 * @contributor Laurent Jouanneau
 *
 * @copyright  2006 Loic Mathaud, 2008-2024 Laurent Jouanneau
 *
 * @see      http://www.jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\SQLite3;

use Jelix\Database\AbstractResultSet;
use Jelix\Database\Exception;

/**
 *
 */
class ResultSet extends AbstractResultSet
{
    /**
     * @var \SQLite3Stmt
     */
    protected $_stmt;

    /**
     * @var \SQLite3Result
     */
    protected $_idResult;

    /**
     * @var Connection
     */
    protected $_conn;

    /**
     * number of rows.
     */
    protected $numRows = 0;

    /**
     * when reaching the end of a result set, sqlite3 api do a rewind
     * we don't want this behavior, to mimic the behavior of other drivers
     * this property indicates that we reached the end.
     */
    protected $ended = false;

    /**
     * contains all unreaded records when
     * rowCount() have been called.
     */
    protected $buffer = array();

    /**
     * @param \SQLite3Result|null $result
     * @param \SQLite3Stmt|null   $stmt
     * @param Connection
     */
    public function __construct($result, $stmt, $conn)
    {
        parent::__construct($result);
        $this->_stmt = $stmt;
        $this->_conn = $conn;
    }

    protected function _fetch()
    {
        if (count($this->buffer)) {
            return array_shift($this->buffer);
        }
        if ($this->ended) {
            return false;
        }
        $res = $this->_idResult->fetchArray(SQLITE3_ASSOC);
        if ($res === false) {
            $this->ended = true;

            return false;
        }
        ++$this->numRows;

        return (object) $res;
    }

    protected function _fetchAssoc()
    {
        if (count($this->buffer)) {
            return array_shift($this->buffer);
        }
        if ($this->ended) {
            return false;
        }
        $res = $this->_idResult->fetchArray(SQLITE3_ASSOC);
        if ($res === false) {
            $this->ended = true;

            return false;
        }
        ++$this->numRows;

        return $res;
    }

    protected function _free()
    {
        $this->numRows = 0;
        $this->buffer = array();
        $this->ended = false;
        // finalize may lead to an error if connection has been closed before
        // the resultset object destruction.
        if ($this->_conn && !$this->_conn->isClosed()) {
            $this->_idResult->finalize();
        }
        $this->_conn = null;
    }

    protected function _rewind()
    {
        $this->numRows = 0;
        $this->buffer = array();
        $this->ended = false;

        return $this->_idResult->reset();
    }

    public function rowCount()
    {
        // the mysqlite3 api doesn't provide a numrows property like any other
        // database. The only way to now the number of rows, is to
        // fetch all rows :-/
        // let's store it into a buffer
        if ($this->ended) {
            return $this->numRows;
        }

        $res = $this->_idResult->fetchArray(SQLITE3_ASSOC);
        if ($res !== false) {
            while ($res !== false) {
                $this->buffer[] = (object) $res;
                $res = $this->_idResult->fetchArray(SQLITE3_ASSOC);
            }
            $this->numRows += count($this->buffer);
        }
        $this->ended = true;

        return $this->numRows;
    }

    public function bindColumn($column, &$param, $type = null)
    {
        throw new Exception('JDb: the sqlite3 connector doesn\'t support the feature "bindColumn"', 404);
    }

    protected function getSqliteType($pdoType)
    {
        $type = array(
            \PDO::PARAM_INT => SQLITE3_INTEGER,
            \PDO::PARAM_STR => SQLITE3_TEXT,
            \PDO::PARAM_LOB => SQLITE3_BLOB,
        );
        if (isset($type[$pdoType])) {
            return $type[$pdoType];
        }

        return SQLITE3_TEXT;
    }

    public function bindParam($parameter, &$variable, $data_type = \PDO::PARAM_STR, $length = 0, $driver_options = null)
    {
        if (!$this->_stmt) {
            throw new Exception('Not a prepared statement');
        }
        return $this->_stmt->bindParam($parameter, $variable, $this->getSqliteType($data_type));
    }

    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        if (!$this->_stmt) {
            throw new Exception('Not a prepared statement');
        }
        return $this->_stmt->bindValue($parameter, $value, $this->getSqliteType($data_type));
    }

    public function columnCount()
    {
        return $this->_idResult->numColumns();
    }

    public function execute($parameters = null)
    {
        if (!$this->_stmt) {
            throw new Exception('Not a prepared statement');
        }
        if (is_array($parameters)) {
            foreach ($parameters as $name => $val) {
                $type = is_integer($val) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $this->_stmt->bindValue($name, $val, $type);
            }
        }
        if ($this->_idResult) {
            $this->_free();
            $this->_idResult = null;
        }
        $this->_idResult = $this->_stmt->execute();

        return ($this->_idResult !== false);
    }
}
