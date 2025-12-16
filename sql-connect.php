
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class SqlConnect {
  public object $db;
  private string $host;
  private string $port;
  private string $dbname;
  private string $password;
  private string $user;

  public function __construct() {
    $this->host = '127.0.0.1';
    $this->port = '3306';
    $this->dbname = 'battleship';
    $this->user = 'root';
    $this->password = 'root';

    try {
        $this->db = new PDO(
            'mysql:host='.$this->host.';port='.$this->port.';dbname='.$this->dbname,
            $this->user,
            $this->password
        );
    } catch (PDOException $e) {
        die("ERREUR PDO : " . $e->getMessage());
    }

    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->db->setAttribute(PDO::ATTR_PERSISTENT, false);
  }

  public function transformDataInDot($data) {
    $dataFormated = [];

    foreach ($data as $key => $value) {
      $dataFormated[':' . $key] = $value;
    }

    return $dataFormated;
  }
}