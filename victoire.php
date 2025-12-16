<?php
session_start();
require_once "sql-connect.php";


$fichier = "etat_joueurs.json";
$etat = json_decode(file_get_contents($fichier), true);

$winner = $etat["winner"] ?? null;


$myshots = [];
$enemyshots = [];

if ($etat["j1"] === session_id()) { //regarde quel utilisateur est quel joueur
    $role = "Joueur 1";
    $myshots = $etat["shots_j1"] ?? [];
    $enemyshots = $etat["shots_j2"] ?? [];

} elseif ($etat["j2"] === session_id()) {
    $role = "Joueur 2";
    $myshots = $etat["shots_j2"] ?? [];
    $enemyshots = $etat["shots_j1"] ?? [];
}
$score_result = "scores.json";

if (file_exists($score_result)) {
    $scores = json_decode(file_get_contents($score_result), true);
} else {
    $scores = ['j1' => 0, 'j2' => 0];
}
$myTotalShots = count($myshots);
$enemyTotalShots = count($enemyshots);

$isWinner = false;

if ($role === "Joueur 1" && $winner === "j1") {
    $isWinner = true;}

if ($role === "Joueur 2" && $winner === "j2"){
     $isWinner = true;}

if ($isWinner ===true) { //message pour le gagnant
    $title = "🎉 Victoire ! 🎉";
    $subtitle = "Vous avez coulé tous les bateaux adverses !";
    $color = "success";
    $image = "bateau.png";

} else{ //message pour le perdant
    $title = "💥 Défaite... 💥";
    $subtitle = "Votre flotte a été entièrement détruite.";
    $color = "danger";
    $image = "https://thumbs.dreamstime.com/b/navire-de-guerre-bismarck-en-feu-dans-la-bataille-finalement-%C3%A9t%C3%A9-coul%C3%A9-par-une-combinaison-navires-britanniques-y-compris-le-282318088.jpg"; // navire coulé
}

function save_state($file, $data) {
  file_put_contents($file, json_encode($data));
}

if (isset($_POST["reset_total"])) {
  $etat = [
    "j1" => null,
    "j2" => null,
    "start" => false
  ];
  save_state($fichier, $etat);

  session_unset();
  session_destroy();

  $sql = new SqlConnect();
    $tables = ['joueur1', 'joueur2'];

    foreach ($tables as $table) {
        $query = "UPDATE $table SET checked = 0"; //rénitialise les deux grilles
        $stmt = $sql->db->prepare($query);
        $stmt->execute();
    }
}
if ($etat["start"] === false) {
  header("Location: pregame.php"); //renvoie sur la page d'accueil
  exit;
}

if (isset($_POST['reset_scores'])) {
    $scores = ['j1' => 0, 'j2' => 0];
    file_put_contents($score_result, json_encode($scores));
}
header('refresh:3'); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fin de partie</title>
<link rel="stylesheet" href="victoire.css">
</head>

<body>

<div class="container container-box">

    <h1 class="text-<?= $color ?>"><?= $title ?></h1>
    <h4><?= $subtitle ?></h4>

    <img src="<?= $image ?>" alt="" width=500px>

    <div class="stats-box">
        <h3>📊 Statistiques</h3>
        <p><strong>Vos tirs :</strong> <?= $myTotalShots ?></p>
        <p><strong>Tirs adverses :</strong> <?= $enemyTotalShots ?></p>
        <h3>🏆 Scores Totaux</h3>
        <table style="width:100%; color:white; margin-top:10px;">
            <tr>
                <th>Joueur 1</th>
                <th>Joueur 2</th>
            </tr>
            <tr>
                <td><?= $scores['j1'] ?></td>
                <td><?= $scores['j2'] ?></td>
            </tr>
        </table>
    </div>
    <form method="post">
        <button class="btn btn-light btn-lg" type ="submit" name="reset_total">
            🔁 Retourner à l'accueil
        </button>
    </form>
    <form method="post">
    <button class="btn btn-light btn-lg" type="submit" name="reset_scores">
        🔄 Réinitialiser les scores totaux
    </button>
</form>
</div>
</body>
</html>
