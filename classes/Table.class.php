<?php

namespace DB;

/**
 * Must be included
 */
include 'Row.class.php';
include 'DBConnection';

/**
 * Describes a Database connected with PDO.
 * Will only work using a MySQL Database.
 *
 * @todo Make a generic class for various DB types.
 */
class Database {

    protected $host = DB_HOST;
    protected $user = DB_USER;
    protected $pass = DB_PASS;
    protected $dbname = DB_NAME;
    protected $dbh;
    protected $error;
    protected $stmt;

    public function __construct() {
// Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
// Set options
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

// Create a new PDO istance
        try {
            $this->dbh = new PDO( $dsn, $this->user, $this->pass, $options );
        }
// Catch any errors
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
        }
    }

    public function query( $query ) {
        $this->stmt = $this->dbh->prepare( $query );
    }

    public function bind( $param, $value, $type = NULL ) {
        if ( is_null( $type ) ) {
            switch ( true ) {
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

    public function execute() {
        return $this->stmt->execute();
    }

    public function resultset() {
        $this->execute();
        return $this->stmt->fetchAll( PDO::FETCH_ASSOC );
    }

    public function single() {
        $this->execute();
        return $this->stmt->fetch( PDO::FETCH_ASSOC );
    }

// Number of affected rows
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

// Transaction methods
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction() {
        return $this->dbh->commit();
    }

    public function cancelTransaction() {
        return $this->dbh->rollBack();
    }

    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }

}

/**
 * Describes a Table.
 *
 * This class needs to be initialized with the Table name.
 *
 * @example $tab = new Table("myTable");
 */
class Table extends Database {

    /**
     * Select all fields.
     * @var string
     */
    protected $selectQuery;

    /**
     * Select by the ID field.
     * @var string
     */
    protected $selectByIDQuery;

    /**
     * Select by the row number (starting from 0).
     * @var string
     */
    protected $selectByRowQuery;

    /**
     * Counts the number of rows in the Table.
     * @var string
     */
    protected $countQuery;

    /**
     * Insert a record in the Table.
     * @var string
     */
    protected $insertQuery;

    /**
     * Update a record in the Table.
     * @var string
     */
    protected $updateQuery;

    /**
     * Delete a record in the Table.
     * @var string
     */
    protected $deleteQuery;

    /**
     * Array containing the Table's columns.
     * @var array<string>
     */
    protected $tableCols;

    /**
     * Creates an istance related to the $tableName table in the Database
     * @param string $tableName
     *
     * @todo Build Queries from $this->tableCols
     */
    public function __construct( $tableName ) {
        parent::__construct();

        $this->query( "DESCRIBE :table" );
        $this->bind( ':table', $tableName );
        $this->tableCols = $this->fetchAll( PDO::FETCH_COLUMN );

        $this->selectQuery = "SELECT * FROM $tableName";
        $this->selectByIDQuery = "SELECT * FROM $tableName WHERE `ID` = :iD";
        $this->selectByRowQuery = "SELECT * FROM $tableName LIMIT :row, 1";
        $this->countQuery = "SELECT COUNT(*) FROM $tableName";
        $this->insertQuery = "INSERT INTO `$tableName` (`Data`, `Titolo`, `Testo`, `Foto`, `DataIns`) VALUES (:data, :titolo, :testo, :foto, :dataIns)";
        $this->updateQuery = "UPDATE $tableName SET Data = :data, Titolo = :titolo, Testo = :testo, Foto = :foto, DataIns = :dataIns WHERE ID = :iD";
        $this->deleteQuery = "DELETE FROM `$tableName` where `ID` = :iD";
    }

    /**
     * Fetches an array of Row objects (defined in Row.class.php)
     * @return array<Row>
     */
    public function fetchAll() {
        $this->query( $this->selectQuery );
        $this->execute();

        return $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
    }

    /**
     * Fetches an array of $num Row objects
     * @param int $num  Number of Row that must be fetched (default is 1)
     * @return array<Row>
     */
    public function fetchSome( $num = 1 ) {
        $this->query( $this->selectQuery . "LIMIT 0, $num" );

        $this->bind( ':num', $num );
        try {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
        }

        return $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
    }

    public function fetchByID( $ID1 ) {
        $ID = intval( $ID1 );
        $this->query( $this->selectByIDQuery );

        $this->bind( ':iD', $ID );
        try {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
        }
        $results = $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
        return $results[0];
    }

    /**
     * Returns a single row, called by his number.
     * By default it retrieves the first row.
     * @param int $row1 Default is 0 (first row);
     * @return Row
     */
    public function fetchByRow( $row1 = 0 ) {
        $row = intval( $row1 );
        $this->query( $this->selectByRowQuery );

        $this->bind( ':row', $row );
        try {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
        }
        $results = $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
        return $results[0];
    }

    public function fetchCount() {
        $this->query( $this->countQuery );
        try {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
        }
        $results = $this->stmt->fetch( PDO::FETCH_BOTH );
        return $results[0];
    }

    /**
     *
     * @param Row|array<Row> $obj
     * @return boolean|string Returns true if successful, error string if error occurred.
     */
    public function insert( $obj ) {
        $this->query( $this->insertQuery );

        if ( is_array( $obj ) ) {
            $this->bindArray( $obj );
            $this->bind( ':dataIns', date( "Y-m-d" ) );
        }
        elseif ( is_object( $obj ) ) {
            if ( get_class( $obj ) == "Row" ) {
                $this->bindObject( $obj );
                $this->bind( ':dataIns', date( "Y-m-d" ) );
            }
        }
        else {
            echo "Invalid parameters (must be array or Row type)";
        }


        try {
            $this->execute();
            return true;
        }
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }

    /**
     * Deletes an entry from the Table.
     *
     * @param int|array<int> $ID1 Takes an ID or an array of ID ( as an overload method )
     * @return boolean|string Returns true if successful,  error string if error occurred.
     */
    public function delete( $ID1 ) {
        if ( is_array( $ID1 ) ) {
            foreach ( $ID1 as $id1 ) {
                $id = intval( $id1 );
                $this->query( $this->deleteQuery );

                $this->bind( ':iD', $id );
                try {
                    $this->execute();
                    return true;
                }
                catch ( PDOException $e ) {
                    $this->error = $e->getMessage();
                    return $this->error;
                }
            }
        }
        else {
            $ID = intval( $ID1 );
            $this->query( $this->deleteQuery );

            $this->bind( ':iD', $ID );
            try {
                $this->execute();
                return true;
            }
            catch ( PDOException $e ) {
                $this->error = $e->getMessage();
                return $this->error;
            }
        }
    }

    /**
     * Updates a record in the Table.
     *
     * @param News $obj     The data of the updated record.
     * @param int $ID      The ID of the record that must be updated.
     * @return boolean|string      Returns true if the query was successful, error string otherwise.
     */
    public function update( $obj, $ID ) {
        $this->query( $this->updateQuery );

        $this->bind( ':iD', $ID );
        $this->bind( ':data', $obj->Data );
        $this->bind( ':titolo', $obj->Titolo );
        $this->bind( ':testo', $obj->Testo );
        $this->bind( ':foto', $obj->Foto );
        $this->bind( ':dataIns', date( "Y-m-d" ) );
        try {
            $this->execute();
            return true;
        }
        catch ( PDOException $e ) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }

    /**
     * Binds an array to a query.
     *
     * @param array $obj
     */
    protected function bindArray( $obj ) {
        foreach ( $this->fields as $field ) {
            $this->bind( ':' . lcfirst( $field ), $obj[$field] );
        }
    }

    /**
     * Binds a Row object to a query.
     *
     * @param Row $obj
     */
    protected function bindObject( $obj ) {
        foreach ( $this->fields as $field ) {
            $this->bind( ':' . lcfirst( $field ), $obj->$field );
        }
    }

}
