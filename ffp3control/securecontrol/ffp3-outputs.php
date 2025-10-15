<!--
  Basé sur le projet de Rui Santos
  Complete project details at https://RandomNerdTutorials.com/control-esp32-esp8266-gpios-from-anywhere/
-->
<?php
    include_once('../../ffp3control/ffp3-database.php');

    $result = getPartOutputs();
    $html_buttons = null;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row["state"] == "1"){
                $button_checked = "checked";
            }
            else {
                $button_checked = "";
            }
            $html_buttons .= '<h3>' . $row["name"] . '</h3><label class="switch"><input type="checkbox" onchange="updateOutput(this)" id="' . $row["id"] . '" ' . $button_checked . '><span class="slider"></span></label>';
        }
    }

    $result2 = getAllBoards(1);
    $html_boards = null;
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $row_reading_time = $row["last_request"];
            // Uncomment to set timezone to - 1 hour (you can change 1 to any number)
            
            $row_reading_time = date("Y-m-d H:i:s", strtotime("$row_reading_time - 1 hours"));

            // Uncomment to set timezone to + 4 hours (you can change 4 to any number)
            //$row_reading_time = date("Y-m-d H:i:s", strtotime("$row_reading_time + 7 hours"));
            $html_boards .= '<p>Dernière requête : '. $row_reading_time . '</p>';
        }
    }
    
    $result3 = getAllOutputs();
    $mail = null;
    $mailNotif = null;
    $aqThr = null;
    $taThr = null;
    $chThr = null;
    $boMat = null;
    $boMid = null;
    $boSoi = null;
    $tGros = null;
    $tPetits = null;
    $tempsRemplissageSec = null;
    $limFlood = null;
    $WakeUp = null;
    $FreqWakeUp = null;



    if ($result3) {
        while ($row = $result3->fetch_assoc()) {
            if ($row["gpio"] == "100"){
                $mail = $row["state"];
            }
            else if ($row["gpio"] == "101"){
                $mailNotif = $row["state"];
            }
            else if ($row["gpio"] == "102"){
                $aqThr = $row["state"];
            }
            else if ($row["gpio"] == "103"){
                $taThr = $row["state"];
            }
            else if ($row["gpio"] == "104"){
                $chThr = $row["state"];
            }
            else if ($row["gpio"] == "105"){
                $boMat = $row["state"];
            }
            else if ($row["gpio"] == "106"){
                $boMid = $row["state"];
            }
            else if ($row["gpio"] == "107"){
                $boSoi = $row["state"];
            }
            else if ($row["gpio"] == "111"){
                $tGros = $row["state"];
            }
            else if ($row["gpio"] == "112"){
                $tPetits = $row["state"];
            }
            else if ($row["gpio"] == "113"){
                $tempsRemplissageSec = $row["state"];
            }
            else if ($row["gpio"] == "114"){
                $limFlood = $row["state"];
            }
            else if ($row["gpio"] == "115"){
                $WakeUp = $row["state"];
            }
            else if ($row["gpio"] == "116"){
                $FreqWakeUp = $row["state"];
            }
        }
    }
?>

<!DOCTYPE html>
<html>
	<head>
		<title>olution iot datas</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="/assets/css/main.css" />
		<noscript><link rel="stylesheet" href="/assets/css/noscript.css" /></noscript>
        <link rel="stylesheet" href="/ffp3/ffp3control/ffp3-style.css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <style>
            .auto-save-field {
                transition: border-color 0.3s ease;
            }
            .auto-save-field.saving {
                border-color: #ffa500 !important;
                box-shadow: 0 0 5px rgba(255, 165, 0, 0.5);
            }
            .auto-save-field.saved {
                border-color: #28a745 !important;
                box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
            }
            .auto-save-field.error {
                border-color: #dc3545 !important;
                box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
            }
            #save-status {
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
            }
            #save-status.saving {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }
            #save-status.saved {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            #save-status.error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>
		<link rel="shortcut icon" type="image/png" href="https://iot.olution.info/images/favico.png"/>
	</head>
	<body class="is-preload">

		 <!-- Wrapper -->
			<div id="wrapper" class="fade-in">
			    
				 <!-- Header -->
					<header id="header">
						<a href="/index.php" class="logo">olution iot datas</a>
					</header>

				 <!-- Nav -->
					<nav id="nav">
						<ul class="links">
							<li><a href="https://iot.olution.info/index.php">olution</a></li>
							<li><a href="https://iot.olution.info/ffp3/aquaponie">le prototype farmflow 3</a></li>
							<li><a href="https://iot.olution.info/n3pp/n3ppdatas/n3pp-data.php">phasmopolis</a></li>
							<li><a href="https://iot.olution.info/msp1/msp1datas/msp1-data.php">le tiny garden</a></li>
						</ul>
						<ul class="icons">
							<li><a href="https://olution.info/course/view.php?id=511" class="icon solid fa-leaf"><span class="label">olution</span></a></li>
							<li><a href="https://farmflow.marout.org/" class="icon solid fa-fish"><span class="label">farmflow</span></a></li>
						</ul>
					</nav>

				 <!-- Main -->
					<div id="main">
						 <!-- Featured Post -->
							<article class="post featured">
								<header class="major">
									<h2>Contrôle du ffp3</h2>
									<p>Il est possible d'agir à distance sur différents actionneurs du système.</p>
									<h4>! A manipuler avec la plus grande des précautions !</h4>
                                    <?php echo $html_boards; ?>
                                    <?php echo $html_buttons; ?>
                                    <br><br>
                                    <div>
                                        <form>
                                            <h3 style="text-align:center">Changer les paramètres</h3>
                                            <div id="save-status" style="text-align: center; margin: 10px 0; font-weight: bold; display: none;">
                                                <span id="save-message"></span>
                                            </div>
                                            <label for="mail">Mail</label>
                                            <input type="text" name="mail" id="mail" value=<?php echo $mail; ?> class="auto-save-field"><br>
                                            <label for="mailNotif">Notification par mail (<?php echo $mailNotif; ?>)</label>
                                            <select id="mailNotif" name="mailNotif" class="auto-save-field">
                                              <option value="checked">oui</option>
                                              <option value="false">non</option>
                                            </select>
                                            <label for="aqThr">Limite aquarium</label>
                                            <input type="number" name="aqThr" min="0" id="aqThr" value=<?php echo $aqThr; ?> class="auto-save-field">
                                            <label for="taThr">Limite réserve</label>
                                            <input type="number" name="taThr" min="0" id="taThr" value=<?php echo $taThr; ?> class="auto-save-field">
                                            <label for="tempsRemplissageSec">Temps de remplissage de l'aquarium</label>
                                            <input type="number" name="tempsRemplissageSec" min="0" id="tempsRemplissageSec" value=<?php echo $tempsRemplissageSec; ?> class="auto-save-field">
                                            <label for="$limFlood">Limite pour le débordement</label>
                                            <input type="number" name="limFlood" min="0" id="limFlood" value=<?php echo $limFlood; ?> class="auto-save-field">
                                            <label for="chauff">Limite chauffage</label>
                                            <input type="number" name="chauff" min="0" id="chauff" value=<?php echo $chThr; ?> class="auto-save-field">
                                            <label for="bouffeMat">Heure de nourriture le matin</label>
                                            <input type="number" name="bouffeMat" min="0" id="bouffeMat" value=<?php echo $boMat; ?> class="auto-save-field">
                                            <label for="bouffeMid">Heure de nourriture le midi</label>
                                            <input type="number" name="bouffeMid" min="0" id="bouffeMid" value=<?php echo $boMid; ?> class="auto-save-field">
                                            <label for="bouffeSoir">Heure de nourriture le soir</label>
                                            <input type="number" name="bouffeSoir" min="0" id="bouffeSoir" value=<?php echo $boSoi; ?> class="auto-save-field">
                                            <label for="tempsGros">Temps de nourrissage des gros poissons</label>
                                            <input type="number" name="tempsGros" min="0" id="tempsGros" value=<?php echo $tGros; ?> class="auto-save-field">
                                            <label for="tempsPetits">Temps de nourrissage des petits poissons</label>
                                            <input type="number" name="tempsPetits" min="0" id="tempsPetits" value=<?php echo $tPetits; ?> class="auto-save-field">
                                            <label for="WakeUp">Forçage éveil</label>
                                            <input type="number" name="WakeUp" min="0" id="WakeUp" value=<?php echo $WakeUp; ?> class="auto-save-field">
                                            <label for="FreqWakeUp">Forçage éveil</label>
                                            <input type="number" name="FreqWakeUp" min="0" id="FreqWakeUp" value=<?php echo $FreqWakeUp; ?> class="auto-save-field">
                                        </form>
                                    </div>
                                </header>
                                <div>
                                    <center>
                                  		<a href="/ffp3/cronpompe.php" class="button small">cron manuel</a>
                        		        <a href="/ffp3/cronlog.txt" class="button small">journal du cron</a>
                        		        <br><br>
                                        <a href="/ffp3/aquaponie" class="button large">Retour aux données</a>
                                    </center>
                                </div>
                            </article>
                        </div>
                    </div>

    <script>
        // Variables pour l'enregistrement automatique
        let saveTimeout = null;
        let isSaving = false;
        const SAVE_DELAY = 1000; // Délai de 1 seconde avant l'enregistrement automatique

        // Fonction pour afficher le statut de sauvegarde
        function showSaveStatus(message, type) {
            const statusDiv = document.getElementById('save-status');
            const messageSpan = document.getElementById('save-message');
            
            statusDiv.className = type;
            messageSpan.textContent = message;
            statusDiv.style.display = 'block';
            
            // Masquer le message après 3 secondes pour les succès
            if (type === 'saved') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
            }
        }

        // Fonction pour mettre à jour l'apparence d'un champ
        function updateFieldAppearance(fieldId, state) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.remove('saving', 'saved', 'error');
                field.classList.add(state);
                
                // Retirer la classe après 2 secondes pour 'saved' et 'error'
                if (state === 'saved' || state === 'error') {
                    setTimeout(() => {
                        field.classList.remove(state);
                    }, 2000);
                }
            }
        }

        // Fonction d'enregistrement automatique avec debounce
        function autoSave() {
            // Annuler le timeout précédent s'il existe
            if (saveTimeout) {
                clearTimeout(saveTimeout);
            }

            // Programmer un nouvel enregistrement
            saveTimeout = setTimeout(() => {
                if (!isSaving) {
                    performAutoSave();
                }
            }, SAVE_DELAY);
        }

        // Fonction qui effectue réellement l'enregistrement
        function performAutoSave() {
            if (isSaving) return;
            
            isSaving = true;
            showSaveStatus('Enregistrement en cours...', 'saving');

            // Marquer tous les champs comme "en cours de sauvegarde"
            const fields = document.querySelectorAll('.auto-save-field');
            fields.forEach(field => {
                updateFieldAppearance(field.id, 'saving');
            });

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "https://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE) {
                    isSaving = false;
                    
                    if (this.status === 200) {
                        showSaveStatus('✓ Paramètres sauvegardés automatiquement', 'saved');
                        
                        // Marquer tous les champs comme "sauvegardés"
                        fields.forEach(field => {
                            updateFieldAppearance(field.id, 'saved');
                        });
                    } else {
                        showSaveStatus('✗ Erreur lors de la sauvegarde automatique', 'error');
                        
                        // Marquer tous les champs comme "erreur"
                        fields.forEach(field => {
                            updateFieldAppearance(field.id, 'error');
                        });
                    }
                }
            }

            // Récupérer toutes les valeurs des champs
            var mail = document.getElementById("mail").value;
            var mailNotif = document.getElementById("mailNotif").value;
            var aqThr = document.getElementById("aqThr").value;
            var taThr = document.getElementById("taThr").value;
            var tempsRemplissageSec = document.getElementById("tempsRemplissageSec").value;
            var limFlood = document.getElementById("limFlood").value;
            var chauff = document.getElementById("chauff").value;
            var bouffeMat = document.getElementById("bouffeMat").value;
            var bouffeMid = document.getElementById("bouffeMid").value;
            var bouffeSoir = document.getElementById("bouffeSoir").value;
            var tempsGros = document.getElementById("tempsGros").value;
            var tempsPetits = document.getElementById("tempsPetits").value;
            var WakeUp = document.getElementById("WakeUp").value;
            var FreqWakeUp = document.getElementById("FreqWakeUp").value;

            var httpRequestData = "action=output_create&mail="+mail+"&mailNotif="+mailNotif+"&aqThr="+aqThr+"&tempsRemplissageSec="+tempsRemplissageSec+"&limFlood="+limFlood+"&taThr="+taThr+"&chauff="+chauff+"&bouffeMat="+bouffeMat+"&bouffeMid="+bouffeMid+"&bouffeSoir="+bouffeSoir+"&tempsGros="+tempsGros+"&tempsPetits="+tempsPetits+"&WakeUp="+WakeUp+"&FreqWakeUp="+FreqWakeUp;
            xhr.send(httpRequestData);
        }

        // Initialisation de l'enregistrement automatique
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter les événements sur tous les champs avec la classe auto-save-field
            const autoSaveFields = document.querySelectorAll('.auto-save-field');
            
            autoSaveFields.forEach(field => {
                // Pour les champs de type number et text
                field.addEventListener('input', autoSave);
                field.addEventListener('change', autoSave);
                
                // Pour les select
                if (field.tagName === 'SELECT') {
                    field.addEventListener('change', autoSave);
                }
            });
        });

        // Fonctions existantes (conservées pour compatibilité)
        function updateOutput(element) {
            var xhr = new XMLHttpRequest();
            if(element.checked){
                xhr.open("GET", "https://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php?action=output_update&id="+element.id+"&state=1", true);
            }
            else {
                xhr.open("GET", "https://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php?action=output_update&id="+element.id+"&state=0", true);
            }
            xhr.send();
        }

        function deleteOutput(element) {
            var result = confirm("Want to delete this output?");
            if (result) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "https://iot.olution.info/ffp3/ffp3control/ffp3-outputs-action.php?action=output_delete&id="+element.id, true);
                xhr.send();
                alert("Output deleted");
                setTimeout(function(){ window.location.reload(); });
            }
        }

    </script>
<!--Scripts -->
	<script src="https://iot.olution.info/assets/js/jquery.min.js"></script>
	<script src="https://iot.olution.info/assets/js/jquery.scrollex.min.js"></script>
	<script src="https://iot.olution.info/assets/js/jquery.scrolly.min.js"></script>
	<script src="https://iot.olution.info/assets/js/browser.min.js"></script>
	<script src="https://iot.olution.info/assets/js/breakpoints.min.js"></script>
	<script src="https://iot.olution.info/assets/js/util.js"></script>
	<script src="https://iot.olution.info/assets/js/main.js"></script>
    </body>
</html>
