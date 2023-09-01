# Présentation du plugin Klereo (bêta)

> :memo: ***Remarque***  
> Il s'agit de la documentation du plugin en version bêta. Les fonctionnalitées à venir sont listées dans la todo-liste
> au début du changelog et ne sont donc pas évoquées ici.

Le plugin Klereo permet de gérer son bassin connecté avec la box Klereo connect avec Jeedom. Analogiquement au [site
officiel](https://connect.klereo.fr/v3) et si l'équipement de l'installation le permet, le plugin donne accès :
- aux différentes mesures,
- aux informations de l'installation,
- à la consigne de chauffage,
- éventuellement aux consignes de pH, de redox et de chlore selon vos droits d'accès,
- au contrôle de la filtration,
- au contrôle de l'éclairage du bassin,
- au contrôle des sorties auxiliaires.

Le plugin Klereo peut donc être utile au propriétaire du bassin comme au pisciniste. Le pisciniste a la possibilité de
créer un équipement par bassin auquel il a accès.

***

# Utilisation

Une fois le plugin installé depuis le market, il faut configurer votre accès à l'API. Il s'agit de l'identifiant et du
mot de passe qui vous servent à vous connecter au [site officiel](https://connect.klereo.fr/v3). Ces informations sont
à renseigner dans la configuration du plugin via le menu Plugins / Gestion des plugins puis sur le plugin Klereo :  
![Gestion du plugin Klereo](../../images/Gestion_du_plugin_Klereo.png)

Là, vous devez remplir les informations de connexion :  
![Informations de connexion](../../images/Informations_de_connexion.png)

Attention à bien cliquer sur le bouton ![Sauvegarder](../../images/Sauvegarder.png) sans quoi votre saisie ne sera pas
sauvegardée.  
Le fait de sauvegarder les identifiants réinitialise le plugin complet. Tous les équipements et toutes les commandes du
plugin sont effacées sans qu'une validation soit demandée, alors soyez prudent.

# Le principe

Un équipement de plugin correspond à un bassin. En fonction des mesures gérées par votre coffret Klereo, du type
des équipements installés et de vos droits d'accès, les commandes info et action nécessaires et possibles sont
automatiquement créées au moment de la sauvegarde de l'équipement.

Les données sont actualisées toutes les 10 minutes, une actualisation manuelle peut être demandée. Les données sont
également actualisées immédiatement après l'exécution d'une commande action afin de raffraichir l'état du bassin dans
Jeedom.

Le nom de toutes les commandes peut être personnalisé. Mais il n'est pas possible d'ajouter ou de supprimer des
commandes manuellement.

La plage des commandes info numériques s'adapte automatiquement à la valeur mesurée afin de ne pas générer d'erreur et
de permettre à Jeedom d'afficher toutes les mesures. La plage peut être personnalisée. Toutefois si elle n'est pas
adaptée à une mesure faite, le plugin la modifiera.

Si des commandes sont supprimées de la base de données, elles seront recréées lors de la sauvegarde de l'équipement.

# Les commandes info

Pour chaque mesure, une commande 'instantanée' et 'en filtration' sont créées. Les commandes 'en filtration' ne sont
actualisées par l'API que lorsque la filtration est active. Le plugin ne fait que afficher les valeurs fournies par
l'API, aucun traitement ou calcul n'est fait.

Sont aussi communiquées, le cas échéant :
- le temps de filtration du jour,
- le temps de filtration total,
- la consommation de pH-minus du jour,
- la consommation de pH-minus totale,
- la consommation de chlore du jour,
- la consommation de chlore totale,
- le temps de chauffage du jour,
- le temps de chauffage total.

Les informations techniques sur le bassin suivantes sont également communiquées :
- le mode de régulation,
- le type de désinfectant,
- le type de correcteur de pH,
- le type de chauffage,
- le niveau d'accès de l'utilisateur,
- la gamme de produit,
- le type de pompe de filtration,
- la gamme d'électrolyseur.

Le nombre et le détail des alertes du bassin sont les valeurs de deux commandes. S'il y a plusieurs alertes, les
messages d'alerte sont séparés par des '\|\|'.

# Les commandes action

Chaque commande action est liée à une commande info afin que la valeur initiale corresponde à la valeur effective.

## Les consignes

Les bassins Klereo ont quatres consignes possibles :
- la consigne de chauffage modifiable par tous les utilisateurs si un système de chauffage est installé,
- les consignes de pH, de Redox et de chlore ne sont modifiables que par le pisciniste ou le support Klereo si le
bassin est équipé d'un système de régulation pour ces valeurs.

## Les sorties

Pour toutes les sorties, une commande info reflétant l'état de la sortie est créée, même pour les sorties suivantes
pour lesquelles seul l'état de la sortie est disponible :
- correcteur de pH,
- désinfectant,
- floculant,
- désinfectant hybride.

Pour la filtration, , les paires de commandes info+action suivantes sont créées :
- 'OFF': verrou qui lorsqu'il vaut '1' empêche le plugin de piloter la sortie,
- 'ON' : commande de marche manuelle pour une pompe on/off,
- 'Consigne' : consigne de vitesse manuelle pour une pompe à plusieurs vitesses,
- 'AUTO' : si une commande de fonctionnement manuel n'est pas actif, gestion de la filtration automatique par le
coffret Klereo. Les temps de filtration sont calculés en fonction de la température de l’eau, du volume, du débit de la
pompe et du mode d'utilisation du bassin,
- 'Régulation' : les plages et durées de filtration seront calculées dynamiquement en fonction des paramètres et des
capteurs du bassin.

Pour les sorties éclairage et auxiliaires, les paires de commandes info+action suivantes sont créées :
- 'OFF': verrou qui lorsqu'il vaut '1' empêche le plugin de piloter la sortie,
- 'ON' : commande de marche manuelle,
- 'Temps de minuterie' : durée (en minutes) durant laquelle la sortie est activée lorsque la commande 'Minuterie' est
envoyée,
- 'Minuterie' : pour piloter la sortie durant le temps configuré si la commande 'ON' vaut '0',
- 'Plage' : pour piloter la sortie selon les plages horaires définies si les commandes 'ON' et 'Minuterie' valent '0'.

Pour le chauffage, les paires de commandes info+action suivantes sont créées :
- 'OFF': verrou qui lorsqu'il vaut '1' empêche le plugin de piloter la sortie,
- 'Régulation' : commande qui peut prendre la valeur :
  - 0 : 'Arrêt' : le chauffage est éteint,
  - 1 : 'Automatique' : l'API contrôle le mode refroidissement ou chauffage en fonction de la consigne et de la
  température du bassin,
  - 2 : 'Refroidissement' : l'appareil gérant la température ne fonctionne que en refroidissement,
  - 3 : 'Chauffage' : l'appareil gérant la température ne fonctionne que en chauffage.

> :memo: ***Remarque***  
> Les modes 'Automatique' et 'Refroidissement' n'auront d’effet que sur les types de chauffage avec pompe à chaleur.

***

> :heart: ***Remerciements***  
> Je tiens à remercier Klereo de m'avoir permis de développer ce plugin et surtout Laurent du service web qui m'a donné
> les informations de l'API en avant-première alors que l'API n'est pas officiellement publique.


