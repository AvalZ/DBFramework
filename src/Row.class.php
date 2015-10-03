<?php


/**
 * Describes a Row object.
 * Public variables are automatically introduced with PDO::FETCH_CLASS.
 *
 * @see Table
 */
class Row {

    /**
     * In every DataBase, the primary key MUST always be called ID
     * @var type
     */
    public $ID;

// Public variables will be introduced using PDO::FETCHCLASS

    public function fromArray( $array ) {
        if ( array_key_exists( "ID", $array ) ) {
            $this->ID = $array["ID"];
        }
        else {
            $this->ID = -1;
        }

        foreach ( $array as $key => $value ) {
            $this->$key = $value;
        }
    }

    public function toArray() {

        $array = get_object_vars( $this );
        /*
          $array = [
          "IdNews" => $this->IdNews,
          "Data" => $this->Data,
          "Titolo" => $this->Titolo,
          "Testo" => $this->Testo,
          "Foto" => $this->Foto,
          "DataIns" => $this->DataIns,
          ];
         */

        return $array;
    }

    /**
     * Magic method used in echo statements.
     *
     * @example $row = new DBRow(); <br/>
     * $row.fromArray( $array ); <br/>
     * echo $row;
     *
     * @param string $glue
     * @return string
     */
    public function __toString() {
        $array = $this->toArray();
        $string = implode( ',', $array );

        return $string;
    }

}
