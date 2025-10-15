<?php

$servername = "localhost";

// REPLACE with your Database name
$dbname = "oliviera_iot";
// REPLACE with Database user
$username = "oliviera_iot";
// REPLACE with Database user password
$password = "Iot#Olution1";

// Keep this API Key value to be compatible with the ESP32 code provided in the project page. 
// If you change this value, the ESP32 sketch needs to match
$api_key_value = "fdGTMoptd5CD2ert3";

$api_key = $sensor = $version = $TempAir = $Humidite = $TempEau = $EauPotager = $EauAquarium = $EauReserve = $diffMaree = $Luminosite = $etatPompeAqua = $etatPompeTank = $etatHeat = $etatUV = $bouffeMatin = $bouffeMidi = $bouffeSoir = $bouffePetits = $bouffeGros = $tempsGros = $tempsPetits = $aqThreshold = $tankThreshold = $chauffageThreshold = $mail = $mailNotif = $resetMode = $tempsRemplissageSec = $limFlood = $WakeUp = $FreqWakeUp = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $api_key = test_input($_POST["api_key"]);
    if($api_key == $api_key_value) {
        $sensor = test_input($_POST["sensor"]);
        $version = test_input($_POST["version"]);
        $TempAir = test_input($_POST["TempAir"]);
        $Humidite = test_input($_POST["Humidite"]);
        $TempEau = test_input($_POST["TempEau"]);
        $EauPotager = test_input($_POST["EauPotager"]);
        $EauAquarium = test_input($_POST["EauAquarium"]);
        $EauReserve = test_input($_POST["EauReserve"]);
        $Luminosite = test_input($_POST["Luminosite"]);
        $EauAquarium = test_input($_POST["EauAquarium"]);
        $EauReserve = test_input($_POST["EauReserve"]);
	    $diffMaree = test_input($_POST["diffMaree"]);
        $etatPompeAqua = test_input($_POST["etatPompeAqua"]);
        $etatPompeTank = test_input($_POST["etatPompeTank"]);
        $etatHeat = test_input($_POST["etatHeat"]);
        $etatUV = test_input($_POST["etatUV"]);
        $bouffeMat = test_input($_POST["bouffeMatin"]);
        $bouffeMidi = test_input($_POST["bouffeMidi"]);
		$bouffeSoir = test_input($_POST["bouffeSoir"]);
		$bouffePetits = test_input($_POST["bouffePetits"]);
		$bouffeGros = test_input($_POST["bouffeGros"]);
		$tempsGros = test_input($_POST["tempsGros"]);
		$tempsPetits = test_input($_POST["tempsPetits"]);
		$aqThreshold = test_input($_POST["aqThreshold"]);
		$tankThreshold = test_input($_POST["tankThreshold"]);
		$chauffageThreshold = test_input($_POST["chauffageThreshold"]);
		$mail = test_input($_POST["mail"]);
		$mailNotif = test_input($_POST["mailNotif"]);
		$resetMode = test_input($_POST["resetMode"]);
		$tempsRemplissageSec = test_input($_POST["tempsRemplissageSec"]);
		$limFlood = test_input($_POST["limFlood"]);
		$WakeUp = test_input($_POST["WakeUp"]);
		$FreqWakeUp = test_input($_POST["FreqWakeUp"]);
		

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        
        $sql = "INSERT INTO ffp3Data2 (sensor, version, TempAir, Humidite, TempEau, EauPotager, EauAquarium, EauReserve, diffMaree, Luminosite, etatPompeAqua, etatPompeTank, etatHeat, etatUV, bouffeMatin, bouffeMidi, bouffePetits, bouffeGros,  aqThreshold, tankThreshold, chauffageThreshold, mail, mailNotif, resetMode, bouffeSoir)
        VALUES ('" . $sensor . "', '" . $version . "', '" . $TempAir . "', '" . $Humidite . "', '" . $TempEau . "', '" . $EauPotager . "', '" . $EauAquarium . "', '" . $EauReserve . "', '" . $diffMaree . "', '" . $Luminosite . "', '" . $etatPompeAqua . "', '" . $etatPompeTank . "', '" . $etatHeat . "', '" . $etatUV . "', '" . $bouffeMat . "', '" . $bouffeMidi . "', '" . $bouffePetits . "', '" . $bouffeGros . "',  '" . $aqThreshold . "', '" . $tankThreshold . "',  '" . $chauffageThreshold . "','" . $mail . "', '" . $mailNotif . "', '" . $resetMode . "', '" . $bouffeSoir . "');
        UPDATE ffp3Outputs2 SET state = '" . $etatPompeAqua . "' WHERE gpio = 16 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $etatPompeTank . "' WHERE gpio = 18 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $etatHeat . "' WHERE gpio = 2 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $etatUV . "' WHERE gpio = 15 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $bouffePetits . "' WHERE gpio = 108 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $bouffeGros . "' WHERE gpio = 109 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $resetMode . "' WHERE gpio = 110 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $mail . "' WHERE gpio = 100 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $mailNotif . "' WHERE gpio = 101 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $aqThreshold . "' WHERE gpio = 102 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $tankThreshold . "' WHERE gpio = 103 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $chauffageThreshold . "' WHERE gpio = 104 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $bouffeMat . "' WHERE gpio = 105 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $bouffeMidi . "' WHERE gpio = 106 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $bouffeSoir . "' WHERE gpio = 107 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $tempsGros . "' WHERE gpio = 111 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $tempsPetits . "' WHERE gpio = 112 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $tempsRemplissageSec . "' WHERE gpio = 113 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $limFlood . "' WHERE gpio = 114 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $WakeUp . "' WHERE gpio = 115 AND board = 1;
        UPDATE ffp3Outputs2 SET state = '" . $FreqWakeUp . "' WHERE gpio = 116 AND board = 1;
        ";
/*$etatPompeAqua = $etatPompeTank = $etatHeat = $etatUV = $bouffeMatin = $bouffeMidi = $bouffeSoir = $bouffePetits = $bouffeGros = $aqThreshold = $tankThreshold = $chauffageThreshold = $mail = $mailNotif = $resetMode = "";

INSERT INTO ffp3Data2 (sensor, version, TempAir, Humidite, TempEau, EauPotager, EauAquarium, EauReserve, Luminosite, etatPompeAqua, etatPompeTank, etatHeat, etatUV, bouffeMatin, bouffeMidi, bouffePetits, bouffeGros,  aqTreshold, tankTreshold, chauffageTreshold, mail, mailNotif, resetMode, bouffeSoir)
        VALUES ('test', 'test', '2', '3', '53', '353', '1', '1', '23', '1', '1', '0', '0', '10', '12', '1','0',  '147', '10',  '12','test', 'test', '1', '10')*/
        if ($conn->multi_query($sql) === TRUE) {
            echo "New record created successfully";
        } 
        else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    
        $conn->close();
    }
    else {
        echo "Wrong API Key provided.";
    }

}
else {
    echo "No data posted with HTTP POST.";
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>