# Plateforme domotique

Permet d'activer un certain nombre de fonctions type ok google
et de les envoyer vers un KODI (pour voir des films), Sonos (pour la musique et le text to speech), 
et Alarme MyFox (Somfy). Voir les exemples d'applications plus bas.


## Requis:
* PHP >=7.2 + extension sqlite
* Pour le text to speech (requiert bcmath extension)
* Sur Google Cloud, creer un compte de service avec clé json et activer l'api youtube et text to speech
* Copier cette clé à la racine du projet sous le nom google_key.json.


## Installation:
```php
    Renseigner le fichier .env
    apt-get install youtube-dl
    Faire un composer update
    chmod +x cron.sh
    renommer /écraser database/database.sqlite.empty en database/database.sqlite
	renommer le fichier .example.env en .env (et l'adapter le password permet de sécuriser l'accès via IFTT dans les urls appelés)
    Faire php artisan migrate pour créer la base de données
```

Configurer des ok google via IFTT https://platform.ifttt.com/ avec des applets type (google assistant, puis webhook vers https://VOTRE_SITE/ia?iftt={{TextField}})

Plus d'informations sur https://www.gameandme.fr/mes-programmes/jouer-nimporte-quelle-musique-sur-un-sonos-gratuitement-via-commande-vocale/

Pour connaitre les infos de son arret de bus, se rapprocher des commentaires du fichier Controller.php function bus.


## Applets:
    * ok google, dis moi une histoire -> /ia?password=password&action=story.start&iftt=histoire&salle=salon
    * ok google, sonos jouer muse -> /ia?password=password&action=music.start&iftt=muse&salle=salon
    * ok google, je voudrais écouter muse -> /ia?password=password&action=music.start&iftt=muse&salle=salon
    * ok google, écouter la radio RTL  -> /ia?password=password&action=radio.start&iftt=iftt&salle=salon
    * ok google, dans combien de temps passe le bus 54 -> /bus/{{TextField}}?password=password
    * ok google, activer l'alarme -> /alarme/on?password=password
    * ok google, désactiver l'alarme -> /alarme/off?password=password
    * ok google, je voudrais visionner toy story -> /ia?password=password&action=movie.start&iftt=toy story&salle=salon
    * ok google, envoie le message -> /tts?txt=phrase
    * ok google, pluie dans l'heure -> /meteo/441620?password=password (pour connaitre le chiffre, regardez les requêtes dans l'onglet network http://www.meteofrance.com/
    * ok google, sonos volume à X -> /sonosvolume/80


## Administration:
    Il y a un scan des fichiers via /cron ou cron.sh.
	
    Pour s'assurer que les fichiers sont bien listés, vous pouvez aller sur /phpliteadmin.php (pass = admin)
	
    dans les tables songs et movies.
	
    Vous pouvez le lancer à la main via php artisan scan:songs et php artisan scan:movies


## Problèmes et dysfonctionnements

## Pb Droits d'accès
    Si pb de base de donnees vide, changer les permissions comme ceci
    chgrp www-data database
    chgrp www-data database/database.sqlite
    chmod g+w database
    chmod g+w database/database.sqlite

    (pour un NAS synology ce n'est pas www-data mais http)


### Pb de timeout pour le download Youtube
    Si pb de timeout sur le téléchargement de musiques depuis Youtube, alors trouver un site ou on heberge du code PHP pour recuperer le MP3.
    S'inspirer de cette commande: 
	
	$cmd= 'youtube-dl --add-metadata --extract-audio -o "'.storage_path().'/tmp.%(ext)s" --audio-format mp3 https://www.youtube.com/watch?v='.$id;