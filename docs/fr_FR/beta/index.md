# Présentation du plugin Klereo (bêta)

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
créer un équipement par bassin.

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

## Le principe

Un équipement de plugin correspond à un bassin. En fonction des mesures gérées par votre coffret Klereo, du type
des équipements installés et de vos droits d'accès, les commandes info et action nécessaires et possibles sont
automatiquement créées au moment de la sauvegarde de l'équipement.

Le nom de toutes les commandes peut être personnalisé. Sinon, il n'est pas possible d'ajouter ou de supprimer des
commandes manuellement.

Pour chaque mesure, une commande 'instantanée' et 'en filtration' sont créées. Les commandes 'en filtration' ne sont
actualisées par l'API que lorsque la filtration est active. Le plugin ne fait que afficher les valeurs fournies par
l'API, aucun traitement ou calcul n'est fait.

La plage des commandes info numériques s'adapte automatiquement à la valeur mesurée afin de ne pas générer d'erreur et
de permettre à Jeedom d'afficher toutes les mesures. La plage peut être personnalisée. Toutefois si elle n'est pas
adaptée une mesure faite, le plugin la modifiera.

Si des commandes sont supprimées de la base de données, elles seront recréées lors de la sauvegarde de l'équipement.


