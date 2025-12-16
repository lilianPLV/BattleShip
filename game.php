<?php
session_start();
include_once('sql-connect.php');

$sql = new SqlConnect(); 
$grid1 = "SELECT * FROM joueur1";  //met la commande  a réaliser dans le sql
$stmt = $sql->db->prepare($grid1); //récupère la table joueur 1
$stmt->execute(); //execute la commande
$grid1 = $stmt->fetchAll(PDO::FETCH_ASSOC);  //affecte à $grid la grille joueur 1

$grid2 = "SELECT * FROM joueur2";
$stmt = $sql->db->prepare($grid2);
$stmt->execute();
$grid2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fichier = "etat_joueurs.json";
$etat = json_decode(file_get_contents($fichier), true);

if (!empty($etat["winner"])) {  //si $etat winner contient une valeur : j1 ou j2 cela renvoie les 2 utilisateurs sur la page victoire
    header("Location: victoire.php");
    exit;
}

if ($etat["j1"] === session_id()) { //si l'utilisateur est j1 = donne role et shots
    $role = "Joueur 1";
    $shots = "shots_j1";
    $enemyshots = "shots_j2";
    
} elseif ($etat["j2"] === session_id()) {
    $role = "Joueur 2";
    $shots = "shots_j2";
    $enemyshots = "shots_j1";
} else {
    $role = "Aucun rôle";
}
$isMyTurn = false;
if ($etat["turn"] === "j1" && $role === "Joueur 1"){          //systeme de tour par tour 
  $isMyTurn = true;                                           //la variable turn dans le .json est sois j1 ou j2 et quand elle a l'un l'autre ne peut plus cliqué
}elseif ($etat["turn"] === "j2" && $role === "Joueur 2"){
  $isMyTurn = true;
}

$shotscoord = $etat[$shots] ?? []; //recup les shots dans etat_joueurs

if ($role == "Joueur 1") {
    $mygrid = []; //affecte les grilles pour joueur1
    foreach($grid1 as $row){
        $letter = $row['gridid'][0];
        $number = intval(substr($row['gridid'],1)) - 1;
        $mygrid[$letter][$number] = $row['boat']; //récupère la présence ou non d'un bateau sur chaque case
    }

    $theirgrid = [];
    foreach($grid2 as $row){
        $letter = $row['gridid'][0];
        $number = intval(substr($row['gridid'],1)) - 1;
        $theirgrid[$letter][$number] = $row['boat'];
    }
} else {
    $mygrid = []; //affecte les grilles pour joueur2
    foreach($grid2 as $row){
        $letter = $row['gridid'][0];
        $number = intval(substr($row['gridid'],1)) - 1;
        $mygrid[$letter][$number] = $row['boat'];
    }

    $theirgrid = [];
    foreach($grid1 as $row){
        $letter = $row['gridid'][0];
        $number = intval(substr($row['gridid'],1)) - 1;
        $theirgrid[$letter][$number] = $row['boat'];
    }
}


function save_state($file, $data) {
  file_put_contents($file, json_encode($data));
}

$letters = range('A','J');
for ($i=0; $i<10; $i++) {
    foreach($letters as $letter) {
        if (!isset($theirgrid[$letter][$i])) { //initialise les grilles 
            $theirgrid[$letter][$i] = 0;
        }
        if (!isset($mygrid[$letter][$i])) {
            $mygrid[$letter][$i]= 0;
        }
    }
}

$coord = $_GET['coord'] ?? null; //récupère la coordonnée cliqué

if ($coord && $isMyTurn && !in_array($coord, $shotscoord)) {
    $shotscoord[] = $coord;
    $etat[$shots] = $shotscoord;

    $etat["turn"] = ($role === "Joueur 1") ? "j2" : "j1"; //change de joueur à chaque fois
    save_state($fichier, $etat);

    $totalHits = 0;
    foreach ($shotscoord as $shot) { //teste pour chaque cas si un bateau est présent et est touché
      $letter = $shot[0];
      $number = intval(substr($shot, 1)) - 1;
        if (isset($theirgrid[$letter][$number]) && $theirgrid[$letter][$number] != 0) {
          $totalHits++; //incrémente dès qu'une case bateau est touché
        }
      }

    if ($totalHits >= 17) { //si toutes les cases bateaux sont touchées
        $etat["winner"] = ($role === "Joueur 1") ? "j1" : "j2"; //fin de partie
        $score_result = "scores.json";

      if (!file_exists($score_result)) { //si personne n'a de victoire on initialise les scores à 0
          $scores = ['j1' => 0, 'j2' => 0];
          file_put_contents($score_result, json_encode($scores)); //change le json avec la valeur de scores
      } else {
          $scores = json_decode(file_get_contents($score_result), true); //tableau php
      }

      if ($etat["winner"] === "j1") { //ajoute une victoire au gagnant
          $scores['j1']++;
      } elseif ($etat["winner"] === "j2") {
          $scores['j2']++;
      }

        file_put_contents($score_result, json_encode($scores)); //change le json avec la valeur de scores

        save_state($fichier, $etat);
        header("Location: victoire.php");
        exit;
    }
    $_POST['cell'] = $coord;
    include('click_case.php');
}


$who_start=(rand(0, 1) === 0) ? "j1" : "j2";

if (isset($_POST["reset_total"])) {
  $etat = [
    "j1" => null,
    "j2" => null,
    "start" => false,
    "turn" => $who_start
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

header('refresh:2'); 
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8">
    <title>Battle-ships</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style_index.css">
  </head>

<body class="p-4">
  <h2 class="mb-3">Battle-ships</h2>
<div class="row">
  <div class="col-6">
    <h4>Votre grille</h4>
    <table class="table table-bordered table-striped text-center align-middle">
      <thead class="table-dark">
        <tr>
          <th></th>
          <?php for ($i=1;$i<=10;$i++): ?>
            <th><?= $i ?></th>
          <?php endfor; ?>
        </tr>
      </thead>

      <tbody>
        <?php foreach($letters as $letter): ?>
        <tr>
          <th><?= $letter ?></th>

          <?php for ($i=1;$i<=10;$i++): 
            $coord1 = $letter;
            $coord2 = $i-1;
            $color = "";

            if ($mygrid[$coord1][$coord2] != 0) 
              $color = "ship";

            $enemyShotsCoord = $etat[$enemyshots] ?? []; 
            $coordactuel = $letter.($i); //récupère les coordonées à l'instant t
            foreach($enemyShotsCoord as $shot) { 
              if ($coordactuel == $shot) {
                if ($mygrid[$coord1][$coord2] == 0){
                  $color ="missself"; //change de couleur la case du coté de la personne attaqué
                } else {
                  $color ="hitself";
                }
                break;
              }
            }
            
            
          ?>
            <td class="<?= $color ?>">
              <a class="button"></a>
            </td>
          <?php endfor; ?>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>

  <div class="col-6">
    <h4>Grille adverse</h4>

    <table class="table table-bordered table-striped text-center align-middle <?= $isMyTurn ? '' : 'disabled-grid' ?>">
      <thead class="table-dark">
        <tr>
          <th></th>
          <?php for ($i=1;$i<=10;$i++): ?><th><?= $i ?></th><?php endfor; ?>
        </tr>
      </thead>

      <tbody>
        <?php foreach($letters as $letter): ?>
        <tr>
          <th><?= $letter ?></th>

          <?php for ($i=1;$i<=10;$i++): 
            $coord1 = $letter;
            $coord2 = $i-1;

            $current = $letter.$i;

            $color = "";
            if (in_array($current, $shotscoord)) {
                if ($theirgrid[$coord1][$coord2] == 0){
                    $color = "miss";
                }
                else{
                    $color = "hit";
                }
            }
          ?>
          <td class="<?= $color ?>">
              <a class="button" href="?coord=<?= $letter.$i ?>"></a>
          </td>
          <?php endfor; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<form method="post">
  <button type="submit" name="reset_total">
    ❌
    Fin de partie (RESET)
  </button>
</form>
</body>
</html>
