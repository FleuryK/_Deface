_Deface
=======

SCRIPT DE SURVEILLANCE D'UN REPERTOIRE

Ce script va annalyser un répertoire défini et constater les changements effectué :
Ajout, Modification, Suppression. C'est tout.

Construction d'un index, constate les changements, envoi d'un mail. Voilà.

INSTALLATION
======

Dans votre PMA ou en ligne de commande, importer le contenu du fichier _deface.sql

Renseignez vos infos de connexion dans le fichier _deface.php

Vous pouvez activer les mails si disponible, ou rediriger la sortie (dans le cas d'une crontab par exemple) dans un fichier.

C'est tout ! Lancez le en ligne de commande avec `php -f _deface.php` et laissez-le indexer votre répertoire de fond en comble.

Il se mettra à jour tout seul à la prochaine éxecution.

LICENCE
=======

Lire, écrire, modifier ... voilà !

Mais évitez les utilisations commerciales (revente du script), parce que bon, c'est mal de se faire de l'argent sur le dos des autres.
