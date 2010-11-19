<?php
require_once('phing/Task.php');

class MigrationTask extends Task {

    private $connection;

    protected $host;

    protected $port;

    protected $name;

    protected $user;

    protected $pass;

    protected $path;

    public function setHost($host) {
        $this->host = $host;
    }

    public function setPort($port) {
        $this->port = $port;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function setPass($pass) {
        $this->pass = $pass;
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function main() {

        if (empty($this->path)) {
            throw new BuildException("Migrations path must be set.");
        }

        $dh = opendir($this->path);

        if (!$dh) {
            throw new BuildException("Unable to open {$this->path} for reading.");
        }

        $files = array();

        while (($file = readdir($dh)) !== false) {

            if ($file[0] == '.') {
                continue;
            }

            $info = pathinfo($file);

            if ($info['extension'] != 'php') {
                continue;
            }

            $files[] = $file;
        }

        sort($files);

        $this->connect();
        $this->begin();

        foreach ($files as $file) {
            $this->migrate($file);
        }

        $this->commit();
    }


    protected function migrate($file) {


        $version = $this->version($file);

        $result = $this->query('SELECT * FROM schema_migrations WHERE version=$1', $version);

        if (empty($result)) {

            echo "[MIGRATING]: {$file}\n";

            $this->run("{$this->path}/{$file}");
            $this->execute("INSERT INTO schema_migrations VALUES($1)", $version);
        }
    }


    protected function version($file) {

        $subject = $file;
        $pattern = '/^[0-9]{3}/';
        $matches = array();

        preg_match($pattern, $subject, $matches);

        if (empty($matches[0])) {
            throw new BuildException("Unable to exctract version from file: {$file}.");
        }


        $version = (int) $matches[0];

        if ($version <= 0) {
            throw new BuildException("Invalid version {$version}, extracted from file {$file}.");
        }

        return $version;
    }

    protected function run($file) {
        require_once($file);
    }


    protected function connect() {

        $dsn = "";

        if (!empty($this->host)) {
            $dsn .= "host={$this->host} ";
        }

        if (!empty($this->port)) {
            $dsn .= "port={$this->port} ";
        }

        if (!empty($this->name)) {
            $dsn .= "dbname={$this->name} ";
        }

        if (!empty($this->user)) {
            $dsn .= "user={$this->user} ";
        }

        if (!empty($this->pass)) {
            $dsn .= "password={$this->pass} ";
        }

        $dsn = trim($dsn);


        $this->connection = pg_connect($dsn);

        if (!is_resource($this->connection)) {
            throw new BuildException("Unable to connect to the database.");
        }

        $result = $this->query("SELECT * FROM information_schema.tables WHERE table_schema='public' AND table_name='schema_migrations'");

        if (empty($result)) {
            $this->begin();
            $this->execute("CREATE TABLE schema_migrations(version INTEGER NOT NULL)");
            $this->commit();
        }

    }

    protected function begin() {
        $this->execute("BEGIN");
    }

    protected function commit() {
        $this->execute("COMMIT");
    }

    protected function execute($sql, $params = null) {

        $sql = preg_replace('/\r|\n|\t/', ' ', $sql);
        $sql = preg_replace('/\s+/', ' ', $sql);

        if (empty($params)) {
            pg_query($this->connection, $sql);

        } else {

            if (!is_array($params)) {
                $params = array($params);
            }

            pg_query_params($this->connection, $sql, $params);
        }

        if (pg_last_error($this->connection)) {
            throw new BuildException(pg_last_error($this->connection));
        }
    }

    protected function query($sql, $params = null) {

        $sql = preg_replace('/\r|\n|\t/', ' ', $sql);
        $sql = preg_replace('/\s+/', ' ', $sql);

        if (empty($params)) {
            $result = pg_query($this->connection, $sql);

        } else {

            if (!is_array($params)) {
                $params = array($params);
            }

            $result = pg_query_params($this->connection, $sql, $params);
        }

        $result = pg_fetch_all($result);

        if (pg_last_error($this->connection)) {
            throw new BuildException(pg_last_error($this->connection));
        }

        return $result;
    }

    protected function queryOne($sql, $params = null) {

        $sql = preg_replace('/\r|\n|\t/', ' ', $sql);
        $sql = preg_replace('/\s+/', ' ', $sql);

        if (empty($params)) {
            $result = pg_query($this->connection, $sql);

        } else {

            if (!is_array($params)) {
                $params = array($params);
            }

            $result = pg_query_params($this->connection, $sql, $params);
        }

        $result = pg_fetch_result($result, 0, 0);

        if (pg_last_error($this->connection)) {
            throw new BuildException(pg_last_error($this->connection));
        }

        return $result;
    }

}
