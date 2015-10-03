<?php

/**
 * Describes a Database connected with PDO.
 * Will only work using a MySQL Database.
 *
 * @todo Make a generic class for various DB types.
 */
class Database
{

    protected $host = DB_HOST;
    protected $user = DB_USER;
    protected $pass = DB_PASS;
    protected $dbname = DB_NAME;
    protected $dbh;
    protected $error;
    protected $stmt;

    public function __construct()
    {
// Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
// Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

// Create a new PDO istance
        try
        {
            $this->dbh = new PDO( $dsn, $this->user, $this->pass, $options );
        }
// Catch any errors
        catch ( PDOException $e )
        {
            $this->error = $e->getMessage();
        }
    }

    public function query( $query )
    {
        $this->stmt = $this->dbh->prepare( $query );
    }

    public function bind( $param, $value, $type = NULL )
    {
        if ( is_null( $type ) )
        {
            switch ( true )
            {
                case is_int( $value ):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool( $value ):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null( $value ):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindValue( $param, $value, $type );
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function resultset()
    {
        $this->execute();
        return $this->stmt->fetchAll( PDO::FETCH_ASSOC );
    }

    public function single()
    {
        $this->execute();
        return $this->stmt->fetch( PDO::FETCH_ASSOC );
    }

// Number of affected rows
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

// Transaction methods
    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }

    public function debugDumpParams()
    {
        return $this->stmt->debugDumpParams();
    }

}

