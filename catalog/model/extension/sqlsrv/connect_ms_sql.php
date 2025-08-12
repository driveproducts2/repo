<?php

class ModelExtensionSqlsrvConnectMsSql extends Model {
    private $conn;

    private function getConnectionDetails() {
        // Query to get the latest connection details from the custom table
        $query = $this->db->query("SELECT * FROM `oc_sqlsrv_config` ORDER BY `id` DESC LIMIT 1");

        if ($query->num_rows) {
            return $query->row;
        }

        return false;
    }

    public function connect() {
        // Get the connection details from the database
        $config = $this->getConnectionDetails();

        if (!$config) {
            $this->log->write('SQLSRV Connection Error: No connection details found');
            return false;
        }

        // Connection parameters
        $serverName = $config['server_name'];
        $connectionOptions = array(
            "Database" => $config['database_name'],
            "UID" => $config['username'],
            "PWD" => $config['password'],
        );

        // Establish the connection
        $this->conn = sqlsrv_connect($serverName, $connectionOptions);

        // Check if the connection is successful
        if ($this->conn === false) {
            // Log the error and return false if the connection failed
            $this->log->write('SQLSRV Connection Error: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        // Return true if the connection is successful
        return $this->conn;
    }

    public function close() {
        if ($this->conn !== null) {
            sqlsrv_close($this->conn);
        }
    }
}