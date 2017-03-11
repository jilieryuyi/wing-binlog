<?php namespace Seals\Library;

/**
 * @author yuyi
 * @created 2016/11/25 22:23
 * @email 297341015@qq.com
 * @property \PDO $pdo
 *
 * 数据库操作pdo实现
 *
 */
class PDO implements DbInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var \PDOStatement
     */
    private $sQuery;

    /**
     * @var bool
     */
    private $bconnected = false;
    private $parameters;
    private $host;
    private $dbname;
    private $password;
    private $user;
    private $lastSql = "";
    private $port = 3306;

    /**
     * @构造函数
     *
     * @param string $user
     * @param string $password
     * @param string $host
     * @param string $dbname
     * @return void
     */
    public function __construct($user, $password, $host, $dbname, $port = 3306)
    {
        $this->parameters = array();
        $this->dbname     = $dbname;
        $this->host       = $host;
        $this->password   = $password;
        $this->user       = $user;

        if ($port)
            $this->port   = $port;

        $this->connect();
    }

    /**
     * @析构函数
     */
    public function __destruct()
    {
        $this->close();
    }

    public function getDatabaseName()
    {
        return $this->dbname;
    }

    public function getHost()
    {
        return $this->host;
    }
    public function getUser()
    {
        return $this->user;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function getPort()
    {
        return $this->port;
    }

    public function getTables()
    {
        $datas = $this->query("show tables");
        return $datas;
    }

    /**
     * @链接数据库
     */
    private function connect()
    {
        $dsn = 'mysql:dbname=' . $this->dbname . ';host=' . $this->host . '';
        try {
            $this->pdo = new \PDO($dsn, $this->user, $this->password, [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            $this->bconnected = true;
        } catch (\PDOException $e) {
            trigger_error("pdo connect error => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
        }
    }


    /**
     * @关闭链接
     */
    private function close()
    {
        $this->pdo = null;
        $this->bconnected = false;
    }

    /**
     * 初始化数据库连接以及参数绑定
     *
     * @param string $query
     * @param array $parameters
     * @return void
     */
    private function init($query, $parameters = null)
    {
        if ($parameters && !is_array($parameters))
            $parameters = [$parameters];

        $this->lastSql = $query;
        if ($parameters)
            $this->lastSql .= " with raw data : ".json_encode($parameters,JSON_UNESCAPED_UNICODE);

        if (!$this->bconnected) {
            $this->connect();
        }
        try {
            if ($this->pdo)
                $this->sQuery = $this->pdo->prepare($query);
            if ($this->sQuery)
                $this->sQuery->execute($parameters);
        } catch (\PDOException $e) {
            $this->close();
            $this->connect();
            trigger_error("pdo init => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
        }
        $this->parameters = array();
    }


    /**
     * 执行SQL语句
     *
     * @param  string $query
     * @param  array  $params
     * @param  int    $fetchmode
     * @return mixed
     */
    public function query($query, $params = null, $fetchmode = \PDO::FETCH_ASSOC)
    {
        $query = preg_replace("/\s+|\t+|\n+/", " ", $query);

        $this->init($query, $params);

        try {
            $rawStatement = explode(" ", $query);
            $statement    = strtolower($rawStatement[0]);

            if ($statement === 'select' || $statement === 'show') {
                if ($this->sQuery)
                    return $this->sQuery->fetchAll($fetchmode);
                else
                    return null;
            }

            if ($statement === 'insert') {
                if ($this->pdo)
                    return $this->pdo->lastInsertId();
                else
                    return 0;
            }

            if ($statement === 'update' || $statement === 'delete') {
                if ($this->sQuery)
                    return $this->sQuery->rowCount();
                else
                    return 0;
            }
        } catch (\PDOException $e) {
            trigger_error("pdo query => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
            $this->close();
            $this->connect();
        }

        return NULL;
    }

    /**
     *  获取最后的自增id
     *
     *  @return string
     */
    public function lastInsertId()
    {
        try {
            if ($this->pdo)
                return $this->pdo->lastInsertId();
            else
                return 0;
        } catch (\PDOException $e) {
            trigger_error("pdo lastInsertId => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
            $this->close();
            $this->connect();
        }
        return 0;
    }

    /**
     * 开启事务
     *
     * @return boolean, true 成功或者 false 失败
     */
    public function startTransaction()
    {
        try {
            if ($this->pdo)
                return $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            trigger_error("pdo startTransaction => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
            $this->close();
            $this->connect();
        }
        return false;
    }

    /**
     *  提交事务
     *
     *  @return boolean, true 成功或者 false 失败
     */
    public function commit()
    {
        try {
            if ($this->pdo)
                return $this->pdo->commit();
        } catch (\PDOException $e) {
            trigger_error("pdo commit => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
            $this->close();
            $this->connect();
        }
        return false;
    }

    /**
     *  回滚事务
     *
     *  @return boolean, true 成功或者 false 失败
     */
    public function rollBack()
    {
        try {
            if ($this->pdo)
                return $this->pdo->rollBack();
        } catch (\PDOException $e) {
            trigger_error("pdo rollBack => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
            $this->close();
            $this->connect();
        }
        return false;
    }


    /**
     * 查询返回行
     *
     * @param  string $query
     * @param  array  $params
     * @param  int    $fetchmode
     * @return array
     */
    public function row($query, $params = null, $fetchmode = \PDO::FETCH_ASSOC)
    {
        try {
            $this->init($query, $params);
            if ($this->sQuery) {
                $result = $this->sQuery->fetch($fetchmode);
                $this->sQuery->closeCursor();
                return $result;
            }
        } catch (\PDOException $e) {
            trigger_error("pdo row => ".json_encode($e->errorInfo,JSON_UNESCAPED_UNICODE));
            var_dump("pdo ".__FUNCTION__,$e->errorInfo);
            $this->close();
            $this->connect();
        }
        return [];
    }

    /**
     * @获取最后执行的sql
     *
     * @return string
     */
    public function getLastSql()
    {
        return $this->lastSql;
    }
}
