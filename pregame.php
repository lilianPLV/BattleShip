<?php
session_start();
$dejaRole = isset($_SESSION["role"]);
$role = $_SESSION["role"] ?? "Aucun rôle";
  
$fichier = "etat_joueurs.json";
if (!file_exists($fichier)) {
  file_put_contents($fichier, json_encode(["j1" => null, "j2" => null, "start" => false,"shots_j1"=>null,"shots_j2"=>null]));
}

$etat = json_decode(file_get_contents($fichier), true);

if ($etat["j1"] === null && $etat["j2"] === null) {
  unset($_SESSION["role"]);
}

function save_state($file, $data) {
  file_put_contents($file, json_encode($data));
}

if (isset($_POST["joueur1"]) && $etat["j1"] === null) {
    $etat["j1"] = session_id();
    $_SESSION["role"] = "Joueur 1";
    save_state($fichier, $etat);
}

if (isset($_POST["joueur2"]) && $etat["j2"] === null) {
    $etat["j2"] = session_id();
    $_SESSION["role"] = "Joueur 2";
    save_state($fichier, $etat);
 }

if (isset($_POST["starting"]) && $etat["j1"] != null && $etat["j2"] != null) {
    $etat["start"] = true;
    save_state($fichier, $etat);
}

if ($etat["start"] === true) {
  header("Location: game.php");
  exit;
}
if (isset($_POST["reset_total"])) {
    $etat = ["j1" => null, "j2" => null,"start" => false];
    save_state($fichier, $etat);
    unset($_SESSION["role"]);
    session_unset();
    session_destroy();
    
     header("Location: pregame.php");
    exit;
}

header('refresh:2');  
  ?>

  <!DOCTYPE html>
  <html>
    <head>
        <meta charset="UTF-8">
        <title>Joueur 1 / Joueur 2</title>
          <link rel="stylesheet" type="text/css" href="style_pregame.css">
    </head>
    <body>
      <h1>Connexion aux rôles</h1>
      <h2>Votre rôle actuel : <strong><?= $role ?></strong></h2>
      <p>
        Joueur 1 : <?= $etat["j1"] ? "🟢 Occupé" : "🔴 Libre" ?><br>
        Joueur 2 : <?= $etat["j2"] ? "🟢 Occupé" : "🔴 Libre" ?>
      </p>

      <form method="post">
        <button type="submit" name="joueur1"
            <?= $etat["j1"] !== null || $dejaRole ? "disabled" : "" ?>>
            🎮 Devenir Joueur 1
        </button>
        <button type="submit" name="joueur2"
            <?= $etat["j2"] !== null || $dejaRole ? "disabled" : "" ?>>
            🎮 Devenir Joueur 2
          </button>
          <button type="submit" name="starting"
              <?= $etat["j1"] == null || $etat["j2"] == null ? "disabled" : "" ?>>
              🎮 Commencer la partie
          </button>
        <button type="submit" name="reset_total">
            ❌ Fin de partie (RESET)
        </button>
      </form>
    </body>
  </html>
