<?php
/**
 * Fichier d'entrée de l'application.
 * @package App
 */

// Définition du chemin vers l'application
define('APP_ROOT', dirname(dirname(__FILE__)));

// Vérification de la présence de la variable d'environnement OFT_ROOT
$oftRoot = getenv('OFT_ROOT');
if (empty($oftRoot)) {
    die('La variable d\'environnement OFT_ROOT doit &ecirc;tre d&eacute;finie.');
}

// Inclusion du fichier d'initialisation du framework
// et définition des constantes
require_once $oftRoot
    . DIRECTORY_SEPARATOR . 'library'
    . DIRECTORY_SEPARATOR . 'bootstrap.php';
    
try {
    Oft_App::run();
} catch(Exception $e) {
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" '
        . '"http://www.w3.org/TR/html4/strict.dtd">' . "\n";
    echo "<html><head><title>Erreur : un problème technique empèche le fonctionnement du site</title></head><body>\n";
    echo "<h1>Erreur : un problème technique empèche le fonctionnement du site.</h1>\n";
    if (APP_ENV!='prod') {
        echo '<h2>Message : ' . $e->getMessage() . "</h2>\n";
        echo "<h2>Exception de type : " . get_class($e) . "</h2>\n";
        echo "<pre>\n";
        echo $e->getTraceAsString();
        echo "</pre>\n";
        echo "<div style=\"text-align:right\"><font size=\"-1\"> ZF v"
            . Zend_Version::VERSION . " - Oft v"
            . Oft_Version::VERSION ."</font></div>";
    }
    echo "</body></html>\n";
}


