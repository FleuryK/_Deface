<?php

/*
    SCRIPT DE SURVEILLANCE D'UN REPERTOIRE
    Ce script va annalyser un répertoire défini et constater les changements effectué :
    Ajout, Modification, Suppression.

    Fonctions :
    Avertissement par email ;
    Listage de tout les fichiers, indexé autour d'une base de données MySQL ;
    Comparation des changements par MD5 du fichier ;
    Compatible Crontab ;

    Lors de la première initialisation, tout les fichiers vont être ajouté, et ça va être le bordel dans votre boite mail.
    Le script peut être long à s'executer: cela dépend de la taille du répertoire à annalyser.
    Dans le cas d'un répertoire contenant à peu près 35Mo de données et 3000 fichiers, cela prend 15 secondes. A tout casser.

    Bref, passons aux options !
*/

error_reporting(E_ALL); // Configure les erreurs pour afficher toute. 
ini_set('display_errors', 0); // Si ceci est à 1 bien sûr.
set_time_limit(0); // Et ça ? Aucun temps limite avant qu'il ne s'arrête pour timeout.

define('EMAIL_ADMIN', 'admin@example.com'); // Email à laquel on envoie le rapport
define('EMAIL_SENDER', 'robotDeface@example.com'); // Adresse du Gentil robot, pour des filtres ?
define('EMAIL_AVERT', '0'); // On active l'envoi d'un mail. Si utilisé via une crontab, désactivez-le EN LE PASSANT À 0.

define('HOME_WWW', '/path/to/dir'); // le répertoire a scanner.
define('DBHOST', 'localhost'); // adresse du serveur SQL
define('DBUSER', 'user'); // Nom d'utilisateur
define('DBPASS', '_deface#motdepasse#siteouaib'); // Mot de passe. Privilégiez un utilisateur distinct
define('DBBASE', 'database_deface'); // Base de données. Si possible une autre base avec l'utilisateur distinct.
define('DBTABLE', '_deface')

// Connecting to the database
mysql_connect(DBHOST, DBUSER, DBPASS) or die('Erreur : ' . mysql_error());
mysql_select_db(DBBASE) or die('Erreur : ' . mysql_error());

// Les dossiers exclus. Par exemple, les répertoires contenant des fichiers de cache.
// FORME : array(HOME_WWW . '/path/to/dir', HOME_WWW . '/path/to/second/dir')
// Note : Si vous exclusez un répertoire bien précis, les sous répertoires seront également exclus.
// Si vous ajouter un dossier à la liste d'exclusions alors que la base a déjà été construite, deux solutions :
// Vous laissez comme ceci, le script s'en chargera
// Vous vider entièrement la table.
$fileAdded = array();
$exclusionFiles = array(HOME_WWW . '/cache');

/* 
    FUNCTION liste_file_hash
    Parcours d'un repertoire, avec recursivité (visite dans les sous répertoires)

    paramètres :
        $dir : dossier à visiter
*/
function liste_file_hash($dir)
{
   GLOBAL $fileAdded;
   GLOBAL $exclusionFiles;
    // It opens the file
    if ($dossier = opendir($dir)) {
        // It searches all folders and files it contains
        while ($fichier = readdir($dossier)) {
            // The path of current folder
            $path = $dir . '/' . $fichier;
            // echo $path . "\r\n";
            if(!in_array($path, $exclusionFiles))
            {
                // If it encounters a file, then you raise the function to search
                // again all files and folders it contains
                if ($fichier != '.' && $fichier != '..' && is_dir($path))
                    liste_file_hash($path);
                // If we are dealing with a file
                elseif ($fichier != '.' && $fichier != '..' && !is_dir($path)) {
                    // echo $path . ' - hash(' . md5_file($path) . ')<br />';
                    // It inserts the path of the file and its MD5 hash
                   $requete_sql = mysql_query('SELECT COUNT(*) FROM '.DBTABLE.' WHERE path = "'.$path.'"'); // Parano jusqu'au bout
                   $res=mysql_result($requete_sql,0);
                   if($res==0)
                   {
                      mysql_query('INSERT INTO `'.DBTABLE.'` ( `file_id` , `path` , `hash` ) VALUES (NULL , \'' . $path . '\', \'' . md5_file($path) . '\');') or die('Erreur : ' . mysql_error());
                      $fileAdded[] = "[A] Le fichier ". $path ." a été ajouté récemment\n";
                   }
                   mysql_free_result($requete_sql);
                }
            }
        }
        closedir($dossier);
    }
}
// INDEXATION
liste_file_hash(HOME_WWW);
    // It retrieves the list of files & their hash
    $requete = 'SELECT * FROM `'.DBTABLE.'`';
    $query = mysql_query($requete) or die('Error : ' . mysql_error());

    $msgDebut = "Bonjour ! Ceci est un message automatique. Quelques changements on eu lieu sur le répertoire suivant :\n";
    $msgDebut .= HOME_WWW . "\n";
    $msgDebut .="Suite à ce rapport, l'index à été mis à jour. Les changements d'aujourd'hui n'apparaitrons pas dans un futur rapport. \n";
    $msgDebut .= "Voici le rapport complet: \n\n";

    $rapport = null;

    // On ajoute au rapport la liste des fichiers ajouté au dossier surveillé
    foreach($fileAdded as $donnees)
    {
      $rapport .= $donnees;
    }

    // Maintenant on compare avec la base de données et les fichiers présents
    while ($row = mysql_fetch_array($query)) {
        
        // Liste d'exclusion
        // J'attend les avis pour une meilleure optimisation :'D
        $arborescence = explode('/', $row['path']);
        $Excluded = 0;
        foreach ($exclusionFiles as $key => $value) 
        {
            if($Excluded==0)
            {
                $arbo_exclusionFiles = explode('/', $value);

                $count_Arbo_exclusionFiles = count($arbo_exclusionFiles);
                for($i=0; $i<=$count_Arbo_exclusionFiles-1;$i++)
                {
                    if(isset($arborescence[$i]))
                    {
                        if($arborescence[$i] == $arbo_exclusionFiles[$i])
                            $Excluded = 1;
                        else
                            $Excluded = 0;
                    }
                }
            }
        }
        
        // Vérifie l'existence des fichiers, on fait un MD5 à chaque fois
        if (file_exists($row['path']) && $Excluded==0) {
            // It calculates the MD5 hash of the file
            $hash_md5 = md5_file($row['path']);
            if ($hash_md5 == false)
            {
                $rapport .= "[E] Impossible de comparer le MD5 du fichier (" . $row['path'] . ")\n";
            }
            else 
            {
                // Si le md5 ne correspond pas, c'est modifié.
                if ($hash_md5 != $row['hash'])
                {
                    $rapport .= "[M] Le fichier " . $row['path'] . " a été modifié (MD5 mismatch)\n";
                    mysql_query('UPDATE `'.DBTABLE.'` SET  `hash` = "'.$hash_md5.'" WHERE `path`="'.$row['path'].'";');
                }
            }
        } 
        else
        { // Si il existe pas
            $rapport .= "[D] " . $row['path'] . " N'est plus présent sur le serveur ...\n";
            mysql_query('DELETE FROM `'.DBTABLE.'` WHERE `path` = "'.$row['path'].'"');
            // KILL IT WITH THE FIRE !
        }
    }

    mysql_free_result($query); // On libère le serveur
    
    // RAPPORT SEND : Ne marche pas avec crontab (dépends des serveurs)
    if (!empty($rapport))
    {
        if(EMAIL_AVERT == 1)
        {
            $entetes = "Content-type: text/plain; Charset=UTF-8\r\n" ;
            $entetes .= "From: " . EMAIL_SENDER . "\r\n";
            $entetes .= "Date: ".date("D, j M Y G:i:s O")."\n";
            $entetes .= "MIME-Version: 1.0\n";
            $send = mail(EMAIL_ADMIN, '[DEFACE] Rapport d\'analyse du répertoire', $msgDebut . $rapport, $entetes);
            if (!$send)
                echo '<p>Unable to send mail</p>';

            

        // mysql_query('TRUNCATE TABLE _deface;'); // pour les tests
        }

        echo $msgDebut . $rapport;
    }
    else
        echo 'No file has been modified';

mysql_close();