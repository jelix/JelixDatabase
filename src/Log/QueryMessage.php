<?php
/**
 * @package     jelix
 * @subpackage  database
 *
 * @author      Laurent Jouanneau
 * @copyright   2005-2023 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Log;

/**
 * class that handles a sql query for a logger.
 */
class QueryMessage
{
    protected $query = '';
    protected $startTime = 0;
    protected $endTime = 0;
    protected $trace = array();
    protected $originalQuery = '';

    public function __construct($query)
    {
        $this->query = $query;
        $this->startTime = microtime(true);

        $this->trace = debug_backtrace();
        array_shift($this->trace); // remove the current __construct call
    }

    public function setRealQuery($query)
    {
        $this->originalQuery = $this->query;
        $this->query = $query;
    }

    public function getOriginalQuery()
    {
        return $this->originalQuery;
    }

    public function endQuery()
    {
        $this->endTime = microtime(true);
    }

    public function getTrace()
    {
        return $this->trace;
    }

    public function getTime()
    {
        return $this->endTime - $this->startTime;
    }

    public function getDao()
    {
        foreach ($this->trace as $t) {
            if (isset($t['class'])) {
                $dao = '';
                $class = $t['class'];
                // support of Jelix Dao
                if ($class == 'jDaoFactoryBase') {
                    if (isset($t['object'])) {
                        $class = get_class($t['object']);
                    } else {
                        $class = 'jDaoFactoryBase';
                        $dao = 'unknow dao, jDaoFactoryBase';
                    }
                }
                if (preg_match('/^cDao_(.+)_Jx_(.+)_Jx_(.+)$/', $class, $m)) {
                    $dao = $m[1].'~'.$m[2];
                }
                if ($dao && isset($t['function'])) {
                    $dao .= '::'.$t['function'].'()';
                }
                if ($dao) {
                    return $dao;
                }
            }
        }

        return '';
    }

    public function getFormatedMessage()
    {
        $message = $this->query."\n".$this->getTime().'ms';
        $dao = $this->getDao();
        if ($dao) {
            $message .= ', from dao:'.$dao."\n";
        }
        if ($this->query != $this->originalQuery) {
            $message .= 'Original query: '.$this->originalQuery."\n";
        }

        $traceLog = '';
        foreach ($this->trace as $k => $t) {
            $traceLog .= "\n\t{$k}\t".(isset($t['class']) ? $t['class'].$t['type'] : '').$t['function']."()\t";
            $traceLog .= (isset($t['file']) ? $t['file'] : '[php]').' : '.(isset($t['line']) ? $t['line'] : '');
        }

        return $message.$traceLog;
    }

    public function __toString()
    {
        return $this->getFormatedMessage();
    }
}
