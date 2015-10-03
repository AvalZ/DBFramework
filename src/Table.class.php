<?php

/**
 * Describes a Table.
 *
 * This class needs to be initialized with the Table name.
 *
 * @example $tab = new Table("myTable");
 */
class Table extends Database
{

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
    public function __construct( $tableName )
    {
        parent::__construct();

        $this->query( "DESCRIBE `" . $tableName . "`" );
        $this->execute();
        $this->tableCols = $this->stmt->fetchAll( PDO::FETCH_COLUMN );
        unset( $this->tableCols[0] );

        $this->selectQuery = "SELECT * FROM $tableName";
        $this->selectByIDQuery = "SELECT * FROM $tableName WHERE `ID` = :iD";
        $this->selectByRowQuery = "SELECT * FROM $tableName LIMIT :row, 1";
        $this->countQuery = "SELECT COUNT(*) FROM $tableName";
        $this->insertQuery = "INSERT INTO `$tableName` (`" . implode( "`, `", $this->tableCols ) . "`) VALUES (:" . implode( ", :", array_map( 'lcfirst', $this->tableCols ) ) . ")";
        // $this->updateQuery = "UPDATE $tableName SET Data = :data, Titolo = :titolo, Testo = :testo, Foto = :foto, DataIns = :dataIns WHERE ID = :iD";
        $this->updateQuery = "UPDATE $tableName SET " . $this->prepareUpdateArray( $this->tableCols ) . " WHERE ID = :iD";
        //var_dump($this->insertQuery);

        $this->deleteQuery = "DELETE FROM `$tableName` where `ID` = :iD";
    }

    /**
     * Fetches an array of Row objects (defined in Row.class.php)
     *
     * @return array<Row>
     */
    public function fetchAll()
    {
        $this->query( $this->selectQuery );
        $this->execute();

        return $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
    }

    /**
     * Fetches an array of $num Row objects
     *
     * @param int $num  Number of Row that must be fetched (default is 1)
     * @return array<Row>
     */
    public function fetchSome( $num = 1 )
    {
        $this->query( $this->selectQuery . "LIMIT 0, $num" );

        $this->bind( ':num', $num );
        try
        {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e )
        {
            $this->error = $e->getMessage();
        }

        return $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
    }

    /**
     * Returns a single entry, called by its ID.
     *
     * @param int $ID1
     * @return Row
     */
    public function fetchByID( $ID1 )
    {
        $ID = intval( $ID1 );
        $this->query( $this->selectByIDQuery );

        $this->bind( ':iD', $ID );
        try
        {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e )
        {
            $this->error = $e->getMessage();
        }
        $results = $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
        return $results[0];
    }

    /**
     * Returns a single row, called by its number.
     * By default it retrieves the first row.
     *
     * @param int $row1 Default is 0 (first row);
     * @return Row
     */
    public function fetchByRow( $row1 = 0 )
    {
        $row = intval( $row1 );
        $this->query( $this->selectByRowQuery );

        $this->bind( ':row', $row );
        try
        {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e )
        {
            $this->error = $e->getMessage();
        }
        $results = $this->stmt->fetchAll( PDO::FETCH_CLASS, "Row" );
        return $results[0];
    }

    /**
     * Returns the number of columns in the Table
     *
     * @return int
     */
    public function fetchCount()
    {
        $this->query( $this->countQuery );
        try
        {
            $this->execute();
        }
        // Catch any errors
        catch ( PDOException $e )
        {
            $this->error = $e->getMessage();
        }
        $results = $this->stmt->fetch( PDO::FETCH_BOTH );
        return $results[0];
    }

    /**
     * Inserts a Row object as a record in this Table
     *
     * @param Row|array<Row> $obj
     * @return boolean|string Returns true if successful, error string if error occurred.
     */
    public function insert( $obj )
    {
        $this->query( $this->insertQuery );

        if ( is_array( $obj ) )
        {
            $this->bindArray( $obj );
            $this->bind( ':dataIns', date( "Y-m-d" ) );
        }
        elseif ( is_object( $obj ) )
        {
            if ( get_class( $obj ) == "Row" )
            {
                $this->bindObject( $obj );
                $this->bind( ':dataIns', date( "Y-m-d" ) );
            }
        }
        else
        {
            echo "Invalid parameters (must be array or Row type)";
        }


        try
        {
            $this->execute();
            return true;
        }
        catch ( PDOException $e )
        {
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
    public function delete( $ID1 )
    {
        if ( is_array( $ID1 ) )
        {
            foreach ( $ID1 as $id1 )
            {
                $id = intval( $id1 );
                $this->query( $this->deleteQuery );

                $this->bind( ':iD', $id );
                try
                {
                    $this->execute();
                    return true;
                }
                catch ( PDOException $e )
                {
                    $this->error = $e->getMessage();
                    return $this->error;
                }
            }
        }
        else
        {
            $ID = intval( $ID1 );
            $this->query( $this->deleteQuery );

            $this->bind( ':iD', $ID );
            try
            {
                $this->execute();
                return true;
            }
            catch ( PDOException $e )
            {
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
    public function update( $obj, $ID )
    {
        $this->query( $this->updateQuery );

        $this->bind( ':iD', $ID );
        $this->bind( ':data', $obj->Data );
        $this->bind( ':titolo', $obj->Titolo );
        $this->bind( ':testo', $obj->Testo );
        $this->bind( ':foto', $obj->Foto );
        $this->bind( ':dataIns', date( "Y-m-d" ) );
        try
        {
            $this->execute();
            return true;
        }
        catch ( PDOException $e )
        {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }

    /**
     * Binds an array to a query.
     *
     * @param array $obj
     */
    protected function bindArray( $obj )
    {
        foreach ( $this->tableCols as $field )
        {
            $this->bind( ':' . lcfirst( $field ), $obj[$field] );
        }
    }

    /**
     * Binds a Row object to a query.
     *
     * @param Row $obj
     */
    protected function bindObject( $obj )
    {
        foreach ( $this->tableCols as $field )
        {
            $this->bind( ':' . lcfirst( $field ), $obj->$field );
        }
    }

    /**
     * Creates the Update query parameters string
     *
     * @param type $array Table columns
     * @return string
     * @example ArrayCol1 = :arrayCol1, ArrayCol2 = :arrayCol2, ArrayCol3 = :arrayCol3, ...
     */
    protected function prepareUpdateArray( $array )
    {
        $newArray = array();
        foreach ( $array as $value )
        {
            $newArray[] = "$value = :" . lcfirst( $value );
        }

        $result = implode( ', ', $newArray );

        return $result;
    }

}
