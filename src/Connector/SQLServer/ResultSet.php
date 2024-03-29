<?php
/**
 * @author     Yann Lecommandoux
 * @contributor Laurent Jouanneau
 *
 * @copyright  2008 Yann Lecommandoux, 2017-2023 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Connector\SQLServer;

use Jelix\Database\AbstractResultSet;
use Jelix\Database\ConnectionConstInterface;
use Jelix\Database\Exception;

/**
 * Layer encapsulation resultset mssql.
 */
class ResultSet extends AbstractResultSet
{
    protected $_cnt;

    protected $nextFetchRow = 0;

    protected $preparedQuery = '';

    protected $parameterNames = '';

    protected $boundParameters = array();

    public function __construct($idResult, $cnt = null, $preparedQuery = '', $parameterNames = array())
    {
        $this->_idResult = $idResult;
        $this->_cnt = $cnt;
        $this->nextFetchRow = SQLSRV_SCROLL_NEXT;
        $this->preparedQuery = $preparedQuery;
        $this->parameterNames = $parameterNames;
    }

    public function fetch()
    {
        if (! $this->_idResult) {
            return false;
        }
        if ($this->_fetchMode == ConnectionConstInterface::FETCH_CLASS) {
            if ($this->_fetchModeCtoArgs) {
                $res = sqlsrv_fetch_object($this->_idResult, $this->_fetchModeParam, $this->_fetchModeCtoArgs, $this->nextFetchRow);
            } else {
                $res = sqlsrv_fetch_object($this->_idResult, $this->_fetchModeParam, array(), $this->nextFetchRow);
            }
        } elseif ($this->_fetchMode == ConnectionConstInterface::FETCH_INTO) {
            $res = sqlsrv_fetch_object($this->_idResult, null, array(), $this->nextFetchRow);
            if ($res) {
                $values = get_object_vars($res);
                $res = $this->_fetchModeParam;
                foreach ($values as $k => $value) {
                    $res->{$k} = $value;
                }
            }
        } else {
            $res = sqlsrv_fetch_object($this->_idResult, null, array(), $this->nextFetchRow);
        }
        $this->nextFetchRow = SQLSRV_SCROLL_NEXT;
        if ($res) {
            $this->applyModifiers($res);
        }

        return $res;
    }

    protected function _fetch()
    {
    }


    public function fetchAssociative()
    {
        if (! $this->_idResult) {
            return false;
        }
        $res = sqlsrv_fetch_array($this->_idResult, SQLSRV_FETCH_ASSOC, $this->nextFetchRow);

        $this->nextFetchRow = SQLSRV_SCROLL_NEXT;
        return $res;
    }

    protected function _fetchAssoc()
    {
    }

    protected function _free()
    {
        return sqlsrv_free_stmt($this->_idResult);
    }

    protected function _rewind()
    {
        $this->nextFetchRow = SQLSRV_SCROLL_FIRST;
    }

    public function rowCount()
    {
        return sqlsrv_num_rows($this->_idResult);
    }

    public function bindColumn($column, &$param, $type = null)
    {
        throw new Exception('JDb: the sqlsrv connector doesn\'t support this feature "bindColumn"', 404);
    }

    public function bindParam($parameter, &$variable, $dataType = \PDO::PARAM_STR, $length = 0, $driverOptions = null)
    {
        if (!$this->preparedQuery) {
            throw new Exception('Not a prepared statement');
        }
        $this->boundParameters[$parameter] = &$variable;

        return true;
    }

    public function bindValue($parameter, $value, $dataType = \PDO::PARAM_STR)
    {
        if (!$this->preparedQuery) {
            throw new Exception('Not a prepared statement');
        }
        $this->boundParameters[$parameter] = $value;

        return true;
    }

    public function columnCount()
    {
        return sqlsrv_num_fields($this->_idResult);
    }

    protected $parametersReferences = array();

    public function execute($parameters = null)
    {
        if (!$this->preparedQuery) {
            throw new Exception('Not a prepared statement');
        }

        if ($parameters === null) {
            $parameters = &$this->boundParameters;
        } else {
            // parameters are given, we should re-prepare the query because
            // of the strange parameters of sqlsrv_prepare...
            sqlsrv_free_stmt($this->_idResult);
            $this->_idResult = null;
        }

        if (count($parameters) != count($this->parameterNames)) {
            throw new Exception('Execute: number of parameters should equals number of parameters declared in the query');
        }

        if (!$this->_idResult) {
            // this is the first call of execute(), so no prepared query yet
            // let's prepare it.
            $this->parametersReferences = array();
            foreach ($this->parameterNames as $k => $name) {
                if (!isset($parameters[$name])) {
                    throw new Exception("Execute: parameter '{$name}' is missing from parameters");
                }
                $this->parametersReferences[] = &$parameters[$name];
            }
            $this->_idResult = sqlsrv_prepare($this->_cnt, $this->preparedQuery, $this->parametersReferences);
        }

        return sqlsrv_execute($this->_idResult);
    }

    public function fetch_array()
    {
        return sqlsrv_fetch_array($this->_idResult);
    }
}
