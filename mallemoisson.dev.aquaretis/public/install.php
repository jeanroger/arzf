<?php
// Session
session_start();

// Configuration PHP spécifique
ini_set('display_errors', 1);
ini_set('magic_quotes_gpc', 0);
ini_set('magic_quotes_runtime', 0);
ini_set('magic_quotes_sybase', 0);

// Chemin vers l'application
define('APP_ROOT', dirname(dirname(__FILE__)));

// Temoin d'installation
$installDoneFile = APP_ROOT . '/data/INSTALL';

// Flag POST
$isPost = $_SERVER['REQUEST_METHOD']=='POST';

// Flag valider
$isValider = $isPost && isset($_POST['submit']) && $_POST['submit']=='Valider';

// Erreurs de soumission
$messages = array();

// Version ZF minimum
$zfMinimalVersion = '1.10.7';

// Version Oft minimum
$oftMinimalVersion = 'G0R1-beta';

// Etapes possibles
$steps = array(
    1 => 'HTACCESS',
    2 => 'SQL_CREATE',
    3 => 'ADMIN_USER',
    4 => 'SQL_LOAD',
    5 => 'END',
);

// Environnement existants
$existingEnvs = array(
    'dev',
    'preprod',
    'prod',
);

// Current step
$step = isset($_SESSION['currentStep']) ? $_SESSION['currentStep'] : '';

// Selection de l'étape (step)
if (!file_exists($installDoneFile)) { // Install possible ?
    httpError();
} else if (isset($_REQUEST['step'])) {
    if (!in_array($_REQUEST['step'], $steps)) {
        die('Etape invalide');
    } else {
        $step = $_REQUEST['step'];
    }
}

$forceStep = null;
if (!getenv('APP_ENV') || !getenv('ZF_ROOT') || !getenv('OFT_ROOT')) {
    $step = 'HTACCESS';
    $forceStep = $step;
} else if (!$step) {
    $step = 'HTACCESS';
    $forceStep = $step;
}

// Stockage en session
$_SESSION['currentStep'] = $step;

// Etapes réalisées
if (!isset($_SESSION['completedSteps'])) {
    $_SESSION['completedSteps'] = array();
}

// Step précédent
$precStep = '';
$nextStep = '';
foreach ($steps as $i => $aStep) {
    if ($aStep==$step) {
        if ($i>1) {
            $precStep = $steps[$i-1];
        }
        if (isset($steps[$i+1])) {
            $nextStep = $steps[$i+1];
        }
        break;
    }
}

$title = getStepTitle($step);



// Test mod_rewrite
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (!in_array('mod_rewrite', $modules)) {
        $messages[] = "ATTENTION : Le module Apache 'mod_rewrite' n'est pas activé. Il est conseillé de l'activer.";
    } else {
        //$messages[] = "Le module Apache 'mod_rewrite' est activé.";
    }
} else {
    //$messages[] = "ATTENTION : apache_get_modules n'existe pas";
}

// step=HTACCESS
if ($step=='HTACCESS') {
    
    // Valeurs de .htaccess - Défaut
    $htaccessValues = array(
        'APP_ENV' => array(
            'value' => '',
            'origines'  => array(),
            'valuesList' => $existingEnvs,
        ),
        'ZF_ROOT' =>  array(
            'value' => '',
            'origines'  => array(),
            'valuesList' => array(),
        ),
        'OFT_ROOT' =>  array(
            'value' => '',
            'origines'  => array(),
            'valuesList' => array(),
        ),
    );
    
    // Valeurs de .htaccess - ENV
    if (getenv('APP_ENV')) {
        $htaccessValues['APP_ENV']['value'] = getenv('APP_ENV');
        $htaccessValues['APP_ENV']['origines'][] = 'Environnement';
    }
    
    if (getenv('ZF_ROOT')) {
        $htaccessValues['ZF_ROOT']['value'] = getenv('ZF_ROOT');
        $htaccessValues['ZF_ROOT']['valuesList'][] = getenv('ZF_ROOT');
        $htaccessValues['ZF_ROOT']['origines'][] = 'Environnement';
    }
    
    if (getenv('OFT_ROOT')) {
        $htaccessValues['OFT_ROOT']['value'] = getenv('OFT_ROOT');
        $htaccessValues['OFT_ROOT']['valuesList'][] = getenv('OFT_ROOT');
        $htaccessValues['OFT_ROOT']['origines'][] = 'Environnement';
    }
    
    // Valeurs de .htaccess - valeurs standards
    if (substr(PHP_OS, 0, 3)!=='WIN') { // Linux
        $parentLibDir = dirname(APP_ROOT) . '/lib';
        if (is_dir($parentLibDir)) {
            // Répertoire standard 'lib'
            $files = glob($parentLibDir . '/ZendFramework/*');
            if ($files!==false && count($files)) {
                $htaccessValues['ZF_ROOT']['valuesList'] =
                    array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
                $htaccessValues['ZF_ROOT']['origines'][] = 'Répertoire parent \'lib\'';
            }
            
            $files = glob($parentLibDir . '/Oft_Framework/*');
            if ($files!==false && count($files)) {
                $htaccessValues['OFT_ROOT']['valuesList'] =
                    array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
                $htaccessValues['OFT_ROOT']['origines'][] = 'Répertoire parent \'lib\'';
            }
        } else if (is_file('/etc/version')) {
            // VM Dev
            $files = glob('/data/libraries/ZendFramework/*');
            if ($files!==false && count($files)) {
                $htaccessValues['ZF_ROOT']['valuesList'] =
                    array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
                $htaccessValues['ZF_ROOT']['origines'][] = 'VM dév';
            }
            
            $files = glob('/data/libraries/Oft_Framework/*');
            if ($files!==false && count($files)) {
                $htaccessValues['OFT_ROOT']['valuesList'] =
                    array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
                $htaccessValues['OFT_ROOT']['origines'][] = 'VM dév';
            }
        } else if (is_dir('/exec/adm/lib')) {
            // Phénix
            $files = glob('/exec/adm/lib/ZendFramework/*');
            if ($files!==false && count($files)) {
                $htaccessValues['ZF_ROOT']['valuesList'] =
                    array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
                $htaccessValues['ZF_ROOT']['origines'][] = 'Phénix';
            }
            
            $files = glob('/exec/adm/lib/Oft_Framework/*');
            if ($files!==false && count($files)) {
                $htaccessValues['OFT_ROOT']['valuesList'] =
                    array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
                $htaccessValues['OFT_ROOT']['origines'][] = 'Phénix';
            }
        }
    }
        
    // Répertoire vendors - ZF
    $files = glob(APP_ROOT . '/vendors/ZendFramework/*');
    if ($files!==false && count($files)) {
        $htaccessValues['ZF_ROOT']['valuesList'] =
            array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
        $htaccessValues['ZF_ROOT']['origines'][] =
            "Répertoire 'vendors' de l'application";
    }
    
    $files = glob(APP_ROOT . '/vendors/Zend_Framework/*');
    if ($files!==false && count($files)) {
        $htaccessValues['ZF_ROOT']['valuesList'] =
            array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
        $htaccessValues['ZF_ROOT']['origines'][] =
            "Répertoire 'vendors' de l'application";
    }

    $files = glob(APP_ROOT . '/vendors/ZendFramework-*');
    if ($files!==false && count($files)) {
        $htaccessValues['ZF_ROOT']['valuesList'] =
            array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
        $htaccessValues['ZF_ROOT']['origines'][] =
            "Répertoire 'vendors' de l'application";
    }
    
    $files = glob(APP_ROOT . '/vendors/Zend_Framework-*');
    if ($files!==false && count($files)) {
        $htaccessValues['ZF_ROOT']['valuesList'] =
            array_merge($htaccessValues['ZF_ROOT']['valuesList'], $files);
        $htaccessValues['ZF_ROOT']['origines'][] =
            "Répertoire 'vendors' de l'application";
    }
        
    // Répertoire vendors - OFT
    $files = glob(APP_ROOT . '/vendors/Oft/*');
    if ($files!==false && count($files)) {
        $htaccessValues['OFT_ROOT']['valuesList'] =
            array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
        $htaccessValues['OFT_ROOT']['origines'][] =
            "Répertoire 'vendors' de l'application";
    }
    
    $files = glob(APP_ROOT . '/vendors/Oft_Framework/*');
    if ($files!==false && count($files)) {
        $htaccessValues['OFT_ROOT']['valuesList'] =
            array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
        $htaccessValues['OFT_ROOT']['origines'][] =
            "Répertoire 'vendors' de l'application";
    }
	
	$files = glob(APP_ROOT . '/vendors/Oft_Framework-*');
	if ($files!==false && count($files)) {
		$htaccessValues['OFT_ROOT']['valuesList'] =
				array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
			$htaccessValues['OFT_ROOT']['origines'][] =
				"Répertoire 'vendors' de l'application";
	}
    	
	$files = glob(APP_ROOT . '/vendors/Oft-*');
	if ($files!==false && count($files)) {
		$htaccessValues['OFT_ROOT']['valuesList'] =
				array_merge($htaccessValues['OFT_ROOT']['valuesList'], $files);
			$htaccessValues['OFT_ROOT']['origines'][] =
				"Répertoire 'vendors' de l'application";
	}
	
    // Valeurs de .htaccess - include_path
    $includePaths = explode(PATH_SEPARATOR, get_include_path());
    foreach ($includePaths as $includePath) {
        if (file_exists($includePath . '/Zend/Version.php')) {
            $htaccessValues['ZF_ROOT']['valuesList'][] = realpath($includePath . '/../');
            $htaccessValues['ZF_ROOT']['origines'][] = 'Include path';
        }
        
        if (file_exists($includePath . '/Oft/Version.php')) {
            $htaccessValues['OFT_ROOT']['valuesList'][] = realpath($includePath . '/../');
            $htaccessValues['OFT_ROOT']['origines'][] = 'Include path';
        }
    }
    
    // Dédoublonnage ZF_ROOT
    foreach ($htaccessValues['ZF_ROOT']['valuesList'] as $k => $value) {
        $htaccessValues['ZF_ROOT']['valuesList'][$k] = realpath($value);
    }
    $htaccessValues['ZF_ROOT']['valuesList'] =
        array_unique($htaccessValues['ZF_ROOT']['valuesList']);
    
    // Dédoublonnage OFT_ROOT
    foreach ($htaccessValues['OFT_ROOT']['valuesList'] as $k => $value) {
        $htaccessValues['OFT_ROOT']['valuesList'][$k] = realpath($value);
    }
    $htaccessValues['OFT_ROOT']['valuesList'] =
        array_unique($htaccessValues['OFT_ROOT']['valuesList']);
    
    // step=HTACCESS - POST
    if ($isPost) {
        // Récupération APP_ENV
        $appEnv = $_POST['APP_ENV'];
        if ($appEnv) {
            $htaccessValues['APP_ENV']['value'] = $appEnv;
            //$htaccessValues['APP_ENV']['origines'] = array();
        }
                
        // Récupération ZF_ROOT
        $zfRoot = $_POST['ZF_ROOT'];
        if ($zfRoot) {
            $htaccessValues['ZF_ROOT']['value'] = $zfRoot;
            //$htaccessValues['ZF_ROOT']['origines'] = array();
        }
        
        // Récupération OFT_ROOT
        $oftRoot = $_POST['OFT_ROOT'];
        if ($oftRoot) {
            $htaccessValues['OFT_ROOT']['value'] = $oftRoot;
            //$htaccessValues['OFT_ROOT']['origines'] = array();
        }
        
        
        // Validation ok par défaut
        $valid = true;
        
        // Vérification APP_ENV
        if (!in_array($htaccessValues['APP_ENV']['value'], $existingEnvs)) {
            $valid = false;
            $messages[] = 'APP_ENV invalide';
        }

        // Vérification ZF_ROOT
        $zfRoot = $htaccessValues['ZF_ROOT']['value'];
        if (!is_dir($zfRoot)) {
            $valid = false;
            $messages[] = 'ZF_ROOT n\'est pas un répertoire';
        } else if (!is_dir($zfRoot . '/library/Zend')) {
            $valid = false;
            $messages[] = 'ZF_ROOT ne contient pas de répertoire \'library/Zend\'';
        } else if (!is_dir($zfRoot . '/extras/library/ZendX')) {
            $valid = false;
            $messages[] = 'ZF_ROOT ne contient pas de répertoire \'extras/library/ZendX\'';
        } else if (!is_dir($zfRoot . '/resources/languages')) {
            $valid = false;
            $messages[] = 'ZF_ROOT ne contient pas de répertoire \'resources/languages\'';
        } else if (!file_exists($zfRoot . '/library/Zend/Version.php')) {
            $valid = false;
            $messages[] = 'ZF_ROOT ne contient pas de fichier \'library/Zend/Version.php\'';
        } else {
            include_once $zfRoot . '/library/Zend/Version.php';
            if (Zend_Version::compareVersion($zfMinimalVersion)>0) {
                $valid = false;
                $version = Zend_Version::VERSION;
                $messages[] = "La version du Zend Framework est inférieure au pré requis ($version < $zfMinimalVersion)";
            } else {
                $htaccessValues['ZF_ROOT']['value'] = realpath($htaccessValues['ZF_ROOT']['value']);
            }
        }
        
        // Vérification OFT_ROOT
        $oftRoot = $htaccessValues['OFT_ROOT']['value'];
        if (!is_dir($oftRoot)) {
            $valid = false;
            $messages[] = 'OFT_ROOT n\'est pas un répertoire';
        } else if (!is_dir($oftRoot . '/library/Oft')) {
            $valid = false;
            $messages[] = 'OFT_ROOT ne contient pas de répertoire \'library/Oft\'';
        } else if (!file_exists($oftRoot . '/library/Oft/Version.php')) {
            $valid = false;
            $messages[] = 'OFT_ROOT ne contient pas de fichier \'library/Oft/Version.php\'';
        } else {
            include_once $oftRoot . '/library/Oft/Version.php';
            if (Oft_Version::compareVersion($oftMinimalVersion)>0) {
                $valid = false;
                $version = Oft_Version::VERSION;
                $messages[] = "La version de l'Oft est inférieure au pré requis ($version < $oftMinimalVersion)";
            } else {
                $htaccessValues['OFT_ROOT']['value'] = realpath($htaccessValues['OFT_ROOT']['value']);
            }
        }
        
        // Création du .htaccess
        if ($valid) {
            
            // Valeurs de substitution
            $createHtaccessValues = array(
                'APP_ENV' => $htaccessValues['APP_ENV']['value'],
                'APP_ROOT' => APP_ROOT,
                'ZF_ROOT' => $htaccessValues['ZF_ROOT']['value'],
                'OFT_ROOT' => $htaccessValues['OFT_ROOT']['value'],
            );
            
            // Inclusion des fichier nécessaires
            include_once $createHtaccessValues['ZF_ROOT'] . '/library/Zend/Exception.php';
            include_once $createHtaccessValues['OFT_ROOT'] . '/library/Oft/Exception.php';
            include_once $createHtaccessValues['OFT_ROOT'] . '/library/Oft/TechException.php';
            include_once $createHtaccessValues['OFT_ROOT'] . '/library/Oft/Install.php';
            
            try {
                $oi = new Oft_Install($createHtaccessValues['APP_ENV']);
                $oi->createHtaccess(APP_ROOT . '/public/.htaccess', $createHtaccessValues);
                $messages[] = "Fichier créé";
                if (!$isValider) {
                    header('Location: install.php?step=' . $nextStep);
                    echo "ok";
                    exit();
                }
                $forceStep = null;
            } catch (Exception $e) {
                $messages[] = "Impossible de créer le fichier .htaccess : " . $e->getMessage();
            }
        }
    }
} else if ($step=='SQL_CREATE') {
    $isCreateSchema = false;
    $isCreateTestSchema = false;
    
    // Inclusion des resources
    $oftRoot = getenv('OFT_ROOT');
    require_once $oftRoot
        . DIRECTORY_SEPARATOR . 'library'
        . DIRECTORY_SEPARATOR . 'bootstrap.php';
        
    // Choix du fichier ini à utiliser
    if (file_exists(APP_ROOT . '/application/config/config.d/db.ini')) {
        $dbConfigFile = realpath(APP_ROOT . '/application/config/config.d/db.ini');
    } else {
        $dbConfigFile = realpath(OFT_ROOT . '/data/project-template/db.ini');
    }
    
    // Lecture du fichier de configuration de la base
    $dbConfig = new Zend_Config_Ini(
        $dbConfigFile, null, true
    );
    
    // Modification des paramètres
    if ($isPost) {
        $valid = true;
        
        // Maj du fichier de configuration
        $appEnv = constant('APP_ENV');
        foreach ($dbConfig->$appEnv->resources->db->params as $k => $v) {
            if (isset($_POST[$k])) {
                $dbConfig->$appEnv->resources->db->params->$k = $_POST[$k];
            }
        }
        
        // Test de la connection au serveur
        try {
            $params = array(
                'host' => $dbConfig->$appEnv->resources->db->params->host,
                'dbname' => '',
                'username' => '',
                'password' => '',
            );
            $db = Zend_Db::factory('Pdo_Mysql', $params);
            $db->getConnection();
        } catch (Exception $e) {
            // Seule l'erreur 'Serveur inconnu' est récupérée
            if (strstr($e->getMessage(), "Unknown MySQL server host")!==false) {
                $valid = false;
                $messages[] = $e->getMessage();
            }
        }
        
        // Création du schéma si nécessaire
        if ($valid && isset($_POST['create_schema']) && APP_ENV=='dev') {
            $isCreateSchema = true;
            
            $dbOptions =  $dbConfig->$appEnv->resources->db->toArray();
            $dbOptions['adapter'] = 'Pdo_Mysql';
            
            try {
                $msgTmp = Oft_Install::createSchema(
                    $dbOptions,
                    $_POST['rootUsername'], $_POST['rootPassword']
                );
                
                $messages[] = 'OK : Schéma \'' . $appEnv . '\' créé';
                
                // test de la connexion
                $appDb = Zend_Db::factory('Pdo_Mysql', $dbOptions['params']);
                $appDb->getConnection();
                
                $messages[] = 'OK : Connection \'' . $appEnv . '\' valide';
                
            } catch (Exception $e) {
                $messages[] = 'ERREUR : ' . $e->getMessage();
                $valid = false;
            }
        }
        
        // Création du schéma de test si nécessaire
        if ($valid && isset($_POST['create_test_schema']) && APP_ENV=='dev') {
            $isCreateTestSchema = true;
            
            // Récupération des valeurs de APP_ENV
            $dbConfig->test->resources->db =
                clone $dbConfig->$appEnv->resources->db;
            $dbConfig->test->resources->db->params->dbname =
                $dbConfig->test->resources->db->params->dbname . '-test';
            
            $dbOptions =  $dbConfig->test->resources->db->toArray();
            $dbOptions['adapter'] = 'Pdo_Mysql';
            
            try {
                $msgTmp = Oft_Install::createSchema(
                    $dbOptions,
                    $_POST['rootUsername'], $_POST['rootPassword']
                );
                $messages[] = 'OK : Schéma \'test\' créé';
                
                // test de la connexion
                $appDb = Zend_Db::factory('Pdo_Mysql', $dbOptions['params']);
                $appDb->getConnection();
                $messages[] = 'OK : Connection \'test\' valide';
                
                // Témoin de création du schéma de test
                $_SESSION['SQL_CREATE']['test_created'] = true;
                
            } catch (Exception $e) {
                $messages[] = 'ERREUR : ' . $e->getMessage();
                $valid = false;
            }
        }
           
        // Création  du fichier de l'application
        if ($valid) {
            try {
                $iniWriter = new Zend_Config_Writer_Ini();
                $iniWriter->write(APP_ROOT . '/application/config/config.d/db.ini', $dbConfig);
                $messages[] = "OK : Fichier 'application/config/config.d/db.ini' créé";
            } catch (Exception $e) {
                $valid = false;
                $messages[] = 'ERREUR : ' . $e->getMessage();
            }
        }
        
        // Création du fichier SQL
        if ($valid) {
            $inputFile = OFT_ROOT . '/data/project-template/database_load.sql';
            $outputFile = APP_ROOT . '/data/install-sql/00_database_load.sql';
            if (file_exists($outputFile)) {
                $messages[] = "Le fichier d'initialisation de la base '$outputFile' existe déjà";
            } else if (!copy($inputFile, $outputFile)) {
                $valid = false;
                $messages[] = "ERREUR : Impossible de copier le fichier d'initialisation de la base";
            } else {
                $outputFile = realpath($outputFile);
                $messages[] = "OK : Création du fichier d'initialisation de la base '$outputFile'";
            }
        }
        
        
        // Valider
        if ($valid) {
            if ($isValider) {
                $messages[] = "Configuration valide";
            } else {
                header('Location: install.php?step=' . $nextStep);
                echo "ok";
                exit();
            }
        }
    }
} else if ($step=='ADMIN_USER') {
    // Inclusion des resources
    $oftRoot = getenv('OFT_ROOT');
    require_once $oftRoot
        . DIRECTORY_SEPARATOR . 'library'
        . DIRECTORY_SEPARATOR . 'bootstrap.php';
    
    
    $sqlFileExists = false;
    $cuids = array();
    $cuid = '';
    $userFiles = glob(APP_ROOT . '/data/install-sql/01_create-user_*.sql');
    if ($userFiles!==false) {
        foreach ($userFiles as $userFile) {
            $sqlFileExists = true;
            $cuids[] = substr($userFile, -12, 8);
            if (!$cuid) {
                $cuid = $cuids[0];
            }
        }
    } else if (isset($_SESSION[$step]['cuid'])) {
        $cuid =  $_SESSION[$step]['cuid'];
    }
    
    
    if ($isPost) {
        
        // Fichier user existe déjà
        if (!$isValider && $sqlFileExists) {
            header('Location: install.php?step=' . $nextStep);
            echo 'ok';
            exit();
        }
        
        // Déja validé et clic sur "next"
        if (!strlen(trim($_POST['password'])) && !$isValider && isset($_SESSION['completedSteps'][$step])) {
            header('Location: install.php?step=' . $nextStep);
            echo 'ok';
            exit();
        }
        
        $valid = true;
        
        // Récupération des valeurs
        $cuid = strtoupper(trim($_POST['cuid']));
        $password = trim($_POST['password']);
        $passwordRepeat = trim($_POST['password_repeat']);
        
        // Validation cuid
        $cuidValidator = new Oft_Validate_Cuid();
        if (!$cuidValidator->isValid($cuid)) {
            $valid = false;
            $messages[] = "ERREUR : Code alliance invalide";
        } else {
            $_SESSION[$step]['cuid'] = $cuid;
        }
        
        
        // Validation password
        if (strlen($password)<8) {
            $valid = false;
            $messages[] = "ERREUR : Le mot de passe doit faire plus de 8 caractères";
        }
        
        if ($password!=$passwordRepeat) {
            $valid = false;
            $messages[] = "ERREUR : Les mots de passe ne correspondent pas";
        }
        
        if ($valid) {
            // Salt & password
            $salt = dechex(mt_rand());
            $password = md5($salt . $password);
            
            $sql = "-- Utilisateur '$cuid'\nSET AUTOCOMMIT=0;\n";

            // Création de l'utilisateur
            $sql .= "INSERT INTO users (`cuid`, `password`, `salt`, `active`) "
                . " VALUES ('$cuid', '$password', '$salt', '1');\n";
            
            // Role 'administrators'
            $sql .= "INSERT INTO acl_role_user (`id_acl_role`, `cuid`)"
                . " VALUES ('1', '$cuid');\n";
                
            $sql .= "COMMIT;\n";
                
            // Création du fichier contenant les ordres a passer
            $sqlFile = APP_ROOT . '/data/install-sql/01_create-user_' . $cuid . '.sql';
            if (!file_put_contents(
                    $sqlFile,
                    $sql
                )) {
                $valid = false;
                $messages[] = "ERREUR : Impossible de créer le fichier '$sqlFile'";
            } else {
                $messages[] = "Fichier de création de l'utilisateur créé";
                
                if (!in_array($cuid, $cuids)) {
                    $cuids[] = $cuid;
                }
                
                $_SESSION['completedSteps'][$step] = 'OK';
                if (!$isValider) {
                    header('Location: install.php?step=' . $nextStep);
                    echo 'ok';
                    exit();
                }
            }
        }
        
    }
} else if ($step=='SQL_LOAD') {
    // Inclusion des resources
    $oftRoot = getenv('OFT_ROOT');
    require_once $oftRoot
        . DIRECTORY_SEPARATOR . 'library'
        . DIRECTORY_SEPARATOR . 'bootstrap.php';
    
    try {
        // Récupère les environnements a créer
        $envs = array(APP_ENV);
        if (isset($_SESSION['SQL_CREATE']['test_created']) && $_SESSION['SQL_CREATE']['test_created']) {
            $envs[] = 'test';
        }
        
        // Fichier de configuration de la base
        $dbConfigFile = realpath(APP_ROOT . '/application/config/config.d/db.ini');
                
        // Récupèration des fichiers a exécuter
        $oi = new Oft_Install();
        $sqlFiles = $oi->getSqlFiles(APP_ROOT . '/data/install-sql');
        
        // Fichier réellement exécutés
        $execSqlFiles = array();
        
        if (!count($sqlFiles)) {
            $messages[] = "Pas de fichiers à exécuter\n";
        }
        
        if ($isPost) {
            foreach ($envs as $env) {
                // Configuration
                $dbConfig = new Zend_Config_Ini($dbConfigFile, $env);
                $dbOptions = $dbConfig->resources->db->toArray();
                
                // Base de donnée
                $db = Zend_Db::factory('Pdo_MySql', $dbOptions['params']);
                    
                foreach ($sqlFiles as $sqlFile) {
                    $sqlLoader = new Oft_Db_SqlParser($sqlFile);
                    $sqlOrders = $sqlLoader->getSqlOrders();
                    foreach ($sqlOrders as $sqlOrder) {
    //                    $messages[] = $sqlOrder;
                        $db->query($sqlOrder);
                    }
                    $execSqlFiles[$env][] = $sqlFile;
                }
                $messages[] = "OK : Fichiers SQL exécutés pour '$env'";
            }
            
            $_SESSION['steps'][$step] = 'OK';
            
            if (!$isValider) {
                header('Location: install.php?step=' . $nextStep);
                echo 'ok';
                exit();
            }
        }
                    
    } catch (Exception $e) {
        $messages[] = "Erreur : " . $e->getMessage();
        if (isset($sqlFile)) {
            $messages[] = "Fichier : " . $sqlFile . "\n";
        }
    }
} else if ($step=='END') {
    if ($isPost && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'supprimerFichier':
                session_destroy();
                unlink($installDoneFile);
                $messages[] = "OK : Fichier supprimé.";
                break;
            case 'emptyCache':
                $cacheDir = APP_ROOT . '/data/cache';
                if (!is_dir($cacheDir)) {
                    $messages[] = "ERR : Le répertoire n'existe pas";
                    break;
                }
                $dh = opendir($cacheDir);
                if (!$dh) {
                    $messages[] = "ERR : Impossible d'accèder au répertoire de cache";
                    break;
                }
                
                $deletedFiles = array();
                $ignoredFiles = array();
                while (($file = readdir($dh))!==false) {
                    if ($file[0]=='.') {
                        continue;
                    }
                    
                    if (is_file($cacheDir . '/' . $file) && unlink($cacheDir . '/' . $file)) {
                        $deletedFiles[] = $file;
                    } else if (is_dir($cacheDir . '/' . $file)) {
                        $ignoredFiles[] = $file;
                    }
                }
                
                if (count($deletedFiles)) {
                    $messages["Fichiers supprimés"] = $deletedFiles;
                } else {
                    $messages[] = "Pas de fichier a supprimer";
                }
                
                if (count($ignoredFiles)) {
                    $messages['Répertoires ignorés'] = $ignoredFiles;
                }
                break;
        }
        
    }
}

$stepStatus = array_search($step, $steps) . '/' . count($steps);
header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Installation Oft_Framework - <?php echo $stepStatus, ' - ', $title; ?></title>
</head>
<body>

<h1 style="text-align: center">Installation Oft_Framework</h1>

<div style="text-align: center">
    
<?php
    $count = 0;
    $stopLink = false;
    echo '|&nbsp;';
    foreach ($steps as $linkStep) {
        $count++;
        if ($linkStep==$step) {
            if ($forceStep==$step) {
                $stopLink = true;
            }
            echo '<b>', $count, ' - ', getStepTitle($linkStep), '</b>&nbsp;|&nbsp;';
        } else if ($stopLink) {
            echo $count, ' - ', getStepTitle($linkStep), '&nbsp;|&nbsp;';
        } else {
            echo '<a href="?step=', $linkStep, '">', $count, ' - ', getStepTitle($linkStep), '</a>&nbsp;|&nbsp;';
        }
    }
?>
</div>
<br/>

<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="POST"><input
    type="hidden" name="step" value="<?php echo $step?>">

<table cellspacing="1" cellpadding="5"
    style="margin-left: auto; margin-right: auto; width: 640px; border: 1px solid black">
    <tr>
        <th colspan="2" style="padding: 10px; font-size: 110%; border-bottom: 1px solid black">
            <h2>
                <?php echo $stepStatus, ' - ', $title; ?>
            </h2>
        
        </th>
    </tr>
<?php if (count($messages)) : ?>
    <tr>
        <td colspan="2" style="border-bottom: 1px solid black;">
            <div>
            <!--
            <h2>Message<?php echo count($messages)>1?'s':''?> : </h2>
            -->
            <?php echo htmlList($messages); ?>
            </div>
        </td>
    </tr>
<?php endif; ?>
<?php if ($step=='HTACCESS') : ?>
    <tr>
        <th style="width: 100px">APP_ENV :</th>
        <td style="width: 300px">
            <?php
                $envs = array(
                    'dev'     => 'Développement',
                    'preprod' => 'Pré production',
                    'prod'    => 'Production',
                );
                echo formSelect('APP_ENV', $envs, $htaccessValues['APP_ENV']['value']);
            ?>
    </tr>
    <?php
        if (count($htaccessValues['APP_ENV']['origines'])) {
            echo '<tr><td>&nbsp;</td><td><b>Origine :</b> ',
                implode(', ', $htaccessValues['APP_ENV']['origines']),
                '</td></tr>';
        }
    ?>
    <tr>
        <td colspan="2">Environnement d'exécution de l'application.</td>
    </tr>
    <tr><td colspan="2"><br/></td></tr>

    <tr>
        <th>ZF_ROOT :</th>
        <td>
            <input id="ZF_ROOT" style="width: 95%" type="text" name="ZF_ROOT"
                value="<?php echo $htaccessValues['ZF_ROOT']['value'];?>">
            <?php
                if (count($htaccessValues['ZF_ROOT']['valuesList'])) {
                    echo '<br/>', formSelect(
                        'ZF_ROOT_SELECT',
                        $htaccessValues['ZF_ROOT']['valuesList'],
                        $htaccessValues['ZF_ROOT']['value'],
                        'ZF_ROOT'
                    );
                } //else {
            ?>
        </td>
    </tr>
    <?php
        if (count($htaccessValues['ZF_ROOT']['origines'])) {
            echo '<tr><td>&nbsp;</td><td><b>Origine :</b> ',
                implode(', ', $htaccessValues['ZF_ROOT']['origines']),
                '</td></tr>';
        }
    ?>
    <tr>
        <td colspan="2">Chemin vers le répertoire d'installation du Zend Framework.<br />
        Ce chemin doit contenir les répertoires : 'library/Zend',
        'extras/library/ZendX' et 'resources/languages'.</td>
    </tr>
    <tr><td colspan="2"><br/></td></tr>

    <tr>
        <th>OFT_ROOT :</th>
        <td>
            <input id="OFT_ROOT" style="width: 95%" type="text" name="OFT_ROOT"
                value="<?php echo $htaccessValues['OFT_ROOT']['value'];?>">
            <?php
                if (count($htaccessValues['OFT_ROOT']['valuesList'])) {
                    echo formSelect(
                        'OFT_ROOT_SELECT',
                        $htaccessValues['OFT_ROOT']['valuesList'],
                        $htaccessValues['OFT_ROOT']['value'],
                        'OFT_ROOT'
                    );
                }
            ?>
        </td>
    </tr>
    <?php
        if (count($htaccessValues['OFT_ROOT']['origines'])) {
            echo '<tr><td>&nbsp;</td><td><b>Origine :</b> ',
                implode(', ', $htaccessValues['OFT_ROOT']['origines']),
                '</td></tr>';
        }
    ?>
    <tr>
        <td colspan="2">Chemin vers le répertoire d'installation du framework OFT.</td>
    </tr>
    <tr><td colspan="2"><br/></td></tr>

<?php elseif ($step==='SQL_CREATE') : ?>
<?php
    foreach ($dbConfig as $name => $section) {
        if ($name!==APP_ENV) {
            continue;
        }
        echo '<tr><th colspan="2">Section \'' . $name . '\'', '</th></tr>';
        echo '<tr><td colspan="2" style="font-size: 80%; text-align: center"> Fichier : ', $dbConfigFile, '</td></tr>';
        foreach ($section->resources->db->params as $key => $value) {
            echo '<tr><th>', $key, ' : </th>', '<td>',
                formText($key, $value), '</td></tr>';
        }
    }
?>
<?php     if (APP_ENV=='dev') : ?>
    <tr>
        <th>Options : </th>
        <td>
            <input type="checkbox" name="create_schema" id="create_schema"
                <?php /*echo $isCreateSchema?'checked':'';*/ ?>>
            <label for="create_schema">Créer le schéma et l'utilisateur</label>
            <?php if (APP_ENV=='dev') : ?>
                <input type="checkbox" name="create_test_schema"
                    id="create_test_schema"
                    <?php /*echo $isCreateSchema?'checked':'';*/ ?>>
                <label for="create_test_schema">Créer le schéma test</label>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th colspan="2" style="text-align: left">Information pour la création du schéma : </th>
    </tr>
    <tr>
        <th>Utilisateur : </th>
        <td><?php echo formText('rootUsername', 'root'); ?></td>
    </tr>
    <tr>
        <th>Mot de passe : </th>
        <td><input type="password" name="rootPassword"></td>
    </tr>
<?php     endif; ?>
<?php elseif ($step=='ADMIN_USER') : ?>
<?php     if ($sqlFileExists) : ?>
<tr>
    <td colspan="2" style="font-size: 80%; ">
        <div style="width: 50%; margin-left: auto; margin-right: auto">
            Fichier de création d'utilisateur existants : <?php echo htmlList($cuids); ?>
        </div>
    </td>
</tr>
<?php     endif; ?>
    <tr>
        <th>Cuid : </th>
        <td><?php echo formText('cuid', $cuid); ?></td>
    </tr>
    <tr>
        <th>Mot de passe : </th>
        <td><input type="password" name="password"></td>
    </tr>
    <tr>
        <th>Mot de passe (encore) : </th>
        <td><input type="password" name="password_repeat"></td>
    </tr>
<?php elseif ($step=='SQL_LOAD') : ?>
    <?php if (!$isPost) : ?>
    <tr>
        <td><b>Fichiers à exécuter : </b><?php echo htmlList($sqlFiles); ?></td>
    </tr>
    <?php else : ?>
    <?php     foreach ($execSqlFiles as $env => $files) : ?>
    <tr>
        <td><b>Fichiers exécutés (<?php echo $env?>) : </b>
            <?php echo htmlList($files); ?>
        </td>
    </tr>
    <?php         if (count($files)!=count($sqlFiles)) : ?>
        <tr>
            <td><b>Fichiers NON exécutés : </b><?php echo htmlList(array_diff($sqlFiles, $files)); ?></td>
        </tr>
    <?php         endif; ?>
    <?php     endforeach; ?>
    <?php endif; ?>
<?php elseif ($step=='END') : ?>
<tr>
    <td colspan="2" style="text-align: center">
        <h2>Accès à cette interface</h2>
        <?php
            $file = realpath($installDoneFile);
            if ($file) :
        ?>
            <div>Vous pouvez supprimer le fichier '<?php echo realpath($installDoneFile); ?>' pour ne plus permettre l'accès à cette interface.</div>
            <button type="submit" name="action" value="supprimerFichier">Supprimer le fichier</button>
        <?php else : ?>
            <div>Le fichier d'installation est supprimé. <br />
            Cette page est maintenant innaccessible.</div>
        <?php endif; ?>
		<br />
        <h2>Vider le cache</h2>
        <div>Pour supprimer les fichiers de cache de l'application.</div>
        <button type="submit" name="action" value="emptyCache">Vider le cache</button>
        <br />
        <h2>Accèder à l'application</h2>
        <div>Accédez à <a href=".">l'application</a></div>.
    </td>
</tr>
<?php endif; ?>
    <tr>
        <td colspan="2" style="text-align: right">
            <?php if ($precStep) : ?>
                <input type="button" onclick="document.location='<?php echo $_SERVER['SCRIPT_NAME']; ?>?step=<?php echo $precStep?>';" value="&lt;&lt; Précédent">
            <?php endif; ?>
            <input type="button" onclick="document.location='<?php echo $_SERVER['SCRIPT_NAME']; ?>';" value="Actualiser">
            <?php if ($step!=='END') : ?>
                <input type="submit" name="submit" value="Valider">
            <?php endif; ?>
            <?php if ($nextStep) : ?>
                <input type="submit" name="nextStep" value="Etape suivante &gt;&gt;">
            <?php endif; ?>
        </td>
    </tr>
</table>
</form>
</body>
</html>
<?php
  //
 // Fonctions
//

function escape($text)
{
    return htmlentities($text, ENT_NOQUOTES, 'UTF-8');
}

function formSelect($name, $values, $selected, $changeId = null)
{
    $onChange = '';
    if ($changeId!==null) {
        $onChange = 'onchange="if (this.value) { document.getElementById(\''
            . $changeId . '\').value=this.value; }" ';
        // Ajout d'une entrée vide
        $values = array('' => '') + $values;
        $selected = 0;
    }
    
    $html = '<select name="' . escape($name) . '" ' . $onChange . '>';
    foreach ($values as $k => $v) {
        $key = is_int($k) ? $v : $k;
        $optionSelected = $selected===$key ? 'selected="selected"' : '';
        $html .= '<option value="'
            . escape($key) . '" '
            . escape($optionSelected) . '>'
            . escape($v) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function formText($name, $value='')
{
    return '<input style="width: 300px" type="text" name="'
        . escape($name) . '"
        value="' . escape($value) . '">';
}

function htmlList($values)
{
    $html = '<ul>';
    foreach($values as $key => $value) {
        $html .= '<li>';
        if (is_string($value)) {
            $html .= escape($value);
        } else if (is_array($value)) {
            $html .= $key  . ' : ';
            $html .= htmlList($value);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

/**
 * En cas d'erreur d'accès => 404
 */
function httpError()
{
    header('HTTP/1.1 404 Not Found', true, 404);
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . "\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>404 Not Found</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>Not Found</h1>\n";
    echo "<p>The requested URL ", $_SERVER['SCRIPT_NAME'], " was not found on this server.</p>\n";
    echo "</body>\n";
    echo "</html>\n";
    die();
}


/**
 * Récupère le titre de l'étape
 * @param string $step
 * @return string
 */
function getStepTitle($step)
{
    // Titre de l'étape
    switch ($step) {
        case 'HTACCESS':
            $title = 'Création du fichier .htaccess';
            break;
        case 'SQL_CREATE':
            $title = 'Définition du schéma';
            break;
        case 'ADMIN_USER':
            $title = 'Définition de l\'utilisateur';
            break;
        case 'SQL_LOAD':
            $title = 'Chargement des données';
            break;
        case 'END':
            $title = 'Fin de l\'installation';
            break;
    }
    return $title;
}