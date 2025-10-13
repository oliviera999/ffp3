<?php
// On modifie l'information : Status
header('Status: 301 Moved Permanently', false, 301);
// On effectue ensuite la redirection vers la nouvelle URL Slim
header('Location: https://iot.olution.info/ffp3/aquaponie');
?>