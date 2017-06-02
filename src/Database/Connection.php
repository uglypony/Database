<?php

namespace IgorV\Database;

use \PDO;

class Connection {

    /**
     * Active instance of PDO.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->pdo = new PDO(...array_values($config));
    }

    /**
     * Start fluent query builder.
     *
     * @param $table
     * @return QueryBuilder
     */
    public function table($table)
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * Perform a SELECT SQL query and return a Collection.
     *
     * @param        $query
     * @param array  $params
     * @param string $class
     * @return Collection
     */
    public function select($query, $params = [], $class = ResultSet::class)
    {
        return new Collection(
            $this->execute($query, $params, $class)->fetchAll()
        );
    }

    /**
     * Execute raw SQL query and return executed PDO statement.
     *
     * @param       $query
     * @param array $params
     * @param       $class
     * @return \PDOStatement
     */
    public function execute($query, $params = [], $class = null)
    {
        try {
            $stmt = $this->pdo->prepare($query);

            if ( ! is_null($class)) {
                $stmt->setFetchMode(PDO::FETCH_CLASS, $class);
            }

            $stmt->execute($params);

            return $stmt;
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Execute SELECT SQL query and return the first row.
     *
     * @param        $query
     * @param array  $params
     * @param string $class
     * @return null
     */
    public function selectOne($query, $params = [], $class = ResultSet::class)
    {
        return $this->execute($query, $params, $class)->fetch();
    }

    /**
     * Execute INSERT query.
     *
     * @param $query
     * @param $params
     * @return int
     */
    public function insert($query, $params)
    {
        return $this->execute($query, $params)->rowCount();
    }

    /**
     * Execute UPDATE query.
     *
     * @param $query
     * @param $params
     * @return int
     */
    public function update($query, $params)
    {
        return $this->execute($query, $params)->rowCount();
    }

    /**
     * Execute DELETE query.
     *
     * @param       $query
     * @param array $params
     * @return int
     */
    public function delete($query, $params = [])
    {
        return $this->execute($query, $params)->rowCount();
    }

    /**
     * Start a transaction, execute callback and commits if no Exception is thrown.
     *
     * @param callable $callback
     * @throws \Exception
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        try {
            $this->beginTransaction();
            $return = $callback();
            $this->commit();

            return $return;
        } catch (\Exception $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Start a transaction.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return bool
     */
    public function commit()
    {
        if ( ! $this->pdo->inTransaction()) throw new \RuntimeException('Can not commit transaction if not currently in transaction');

        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction.
     *
     * @return bool
     */
    public function rollBack()
    {
        if ( ! $this->pdo->inTransaction()) throw new \RuntimeException('Can not rollback transaction if not currently in transaction');

        return $this->pdo->rollBack();
    }

    /**
     * Get current PDO instance.
     *
     * @return PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * Tunnel every non-existent call to PDO object.
     *
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->pdo->$method(...$arguments);
    }
}
