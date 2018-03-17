<?php namespace Wing\Library;

/**
 * @author yuyi
 * @created 2016/11/25 22:23
 * @email 297341015@qq.com
 * @property \PDO $pdo
 *
 * 数据库操作pdo实现
 *
 */
class PDO implements IDb
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var \PDOStatement
     */
    private $statement;

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
     * 构造函数
     */
    public function __construct()
    {
        $config = load_config("app");

        if (!is_array($config)) {
            if (WING_DEBUG)
            wing_debug("数据库配置错误");
            exit;
        }

        $config = $config["mysql"];

        $this->parameters = array();
        $this->dbname     = $config["db_name"];
        $this->host       = $config["host"];
        $this->password   = $config["password"];
        $this->user       = $config["user"];
        $this->port       = $config["port"];

        $this->connect();
    }

    /**
     * @析构函数
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 获取db名称
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->dbname;
    }

    /**
     * 获取host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 获取user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 获取password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 获取连接端口
     *
     * @return int
     */
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
        $dsn = 'mysql:dbname=' . $this->dbname . ';host=' . $this->host . ';port='.$this->port;
        try {
            $this->pdo = new \PDO(
                $dsn,
                $this->user,
                $this->password,
                [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            $this->bconnected = true;
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

            sleep(1);
            $this->connect();

            if (WING_DEBUG) {
                ("mysql连接异常");
            }
        }
    }


    /**
     * @关闭链接
     */
    private function close()
    {
        $this->pdo        = null;
        $this->bconnected = false;
    }

    /**
     * 初始化数据库连接以及参数绑定
     *
     * @param string $query
     * @param array $parameters
     * @return bool
     */
    private function init($query, $parameters = null)
    {
        if ($parameters && !is_array($parameters)) {
            $parameters = [$parameters];
        }

        $this->lastSql = $query;

        if ($parameters) {
            $this->lastSql .= ", with data: " . json_encode($parameters, JSON_UNESCAPED_UNICODE);
        }

        if (!$this->bconnected) {
            $this->connect();
        }

        try {
            if (!$this->pdo) {
                return false;
            }

            $this->statement = $this->pdo->prepare($query);

            if (!$this->statement) {
                return false;
            }

            return $this->statement->execute($parameters);
        } catch (\PDOException $e) {
            $this->close();
            $this->connect();

            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }
        }
        $this->parameters = array();
        return false;
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
        $query    = preg_replace("/\s+|\t+|\n+/", " ", $query);
        $init_res = $this->init($query, $params);

        try {
            $rawStatement = explode(" ", $query);
            $statement    = strtolower($rawStatement[0]);

            if ($statement === 'select' || $statement === 'show') {
                if (!$this->statement) {
                    return null;
                }

                return $this->statement->fetchAll($fetchmode);
            }

            if ($statement === 'insert') {
                if (!$this->pdo) {
                    return null;
                }

                return $this->pdo->lastInsertId();
            }

            if ($statement === 'update' || $statement === 'delete') {
                if (!$this->statement) {
                    return 0;
                }

                return $this->statement->rowCount();
            }
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

            $this->close();
            $this->connect();
        }

        return $init_res;
    }

    /**
     *  获取最后的自增id
     *
     *  @return string
     */
    public function lastInsertId()
    {
        try {
            if (!$this->pdo) {
                return 0;
            }
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

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
            if (!$this->pdo) {
                return false;
            }

            return $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

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
            if (!$this->pdo) {
                return false;
            }

            return $this->pdo->commit();
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

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
            if (!$this->pdo) {
                return false;
            }

            return $this->pdo->rollBack();
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

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

            if ($this->statement) {
                $result = $this->statement->fetch($fetchmode);
                $this->statement->closeCursor();
                return $result;
            }
        } catch (\PDOException $e) {
            if (WING_DEBUG) {
                var_dump(__CLASS__."::".__FUNCTION__, $e->errorInfo);
            }

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

    public function getDatabases()
    {
        $data = $this->query('show databases');
        $res  = [];

        foreach ($data as $row) {
            $res[] = $row["Database"];
        }

        return $res;
    }
}
