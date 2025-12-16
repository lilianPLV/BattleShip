<?php
include_once('sql-connect.php');

if (isset($_POST["cell"])) {
  $sql = new SqlConnect();

  $player = $_SESSION["role"] === 'joueur1' ?  'joueur2' : 'joueur1';

  $query = '
    UPDATE '.$player.'
    SET checked = 1
    WHERE gridid = :cell;
  ';

  $req = $sql->db->prepare($query);
  $req->execute(['cell' => $_POST["cell"]]);

  header("Location: game.php");

  exit;
}