<?php

define("HOST", $host);
define("DBNAME", $dbname);
define("UNAME", $dbuser);
define("PASSWD", $dbpass);
define("CHARSET", $dbcharset);

class Has_Model extends PDO
{
    public $sql = '';

    public function __construct()
    {
        try {
            parent::__construct("mysql:host=" . HOST . ";dbname=" . DBNAME, UNAME, PASSWD);
            $this->query('SET CHARACTER SET ' . CHARSET);
            $this->query('SET NAMES ' . CHARSET);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $error) {
            $error->getMessage();
        }
    }

}
?>