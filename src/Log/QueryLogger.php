<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2005-2023 Laurent Jouanneau
 *
 * @see      https://jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
namespace Jelix\Database\Log;

use Psr\Log\LoggerInterface;

class QueryLogger implements QueryLoggerInterface
{

    /**
     * @var string the query effectively executed
     */
    protected $query = '';

    /**
     * @var string the query given before the execution of the query
     */
    protected $originalQuery = '';

    /**
     * @var int the time of the start of the execution
     */
    protected $startTime = 0;

    /**
     * @var int the time of after the execution
     */
    protected $endTime = 0;

    /**
     * @var array stack trace
     */
    protected $trace = array();

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @param  LoggerInterface|null  $logger the logger to use to output message
     * during the endQuery() call.
     */
    public function __construct($logger = null)
    {
        $this->logger = $logger;
    }

    public function startQuery($query)
    {
        $this->query = $this->originalQuery = $query;
        $this->startTime = microtime(true);

        $this->trace = debug_backtrace();
        array_shift($this->trace); // remove the current call
    }


    public function endQuery($realQuery = '')
    {
        $this->endTime = microtime(true);

        if ($realQuery) {
            $this->query = $realQuery;
        }

        if ($this->logger) {
            $this->logger->debug($this->getFormatedMessage());
        }
    }

    public function getOriginalQuery()
    {
        return $this->originalQuery;
    }

    public function getExecutedQuery()
    {
        return $this->query;
    }


    public function getTrace()
    {
        return $this->trace;
    }

    public function getTime()
    {
        return $this->endTime - $this->startTime;
    }

    public function getFormatedMessage()
    {
        $message = $this->query."\n".$this->getTime()."ms \n";

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
