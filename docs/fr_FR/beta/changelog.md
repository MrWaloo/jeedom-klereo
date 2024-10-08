# Changelog Klereo bêta

> :memo: ***Remarque***  
> Si une mise à jour du plugin en version bêta est disponible sans détails correspondants sur cette page, cela signifie
> que seule la documentation a été mise à jour.

## TODO
- Revoir la gestion du cache dès que Jeedom V4.4.10 sortira (gestion du lifetime) et passer la version mini de Jeedom en 4.4.10
- Revoir les messages de log pour le passage en stable
- Créer un widget
- Permettre de modifier les plages horaires du mode 'Plage' pour les sorties qui peuvent être pilotées dans ce mode

## 08/10/2024 V1.0 stable

La version bêta devient stable, les deux versions sont alignées

## 03/09/2024 V0.7 beta

Documentation OK

### Corrections
- Calcul de la consommation de chlore liquide et chlore produit

### Fonctionalités
- Ajout de ces informations (si disponible) :
  - la production journalière de chlore par électrolyse,
  - la consommation de chlore liquide du jour,
  - la consommation de chlore liquide totale,
  - la durée d'injection de chlore liquide du jour,
  - la durée d'injection de chlore liquide totale
- Le plugin n'intérrogera plus le site de Klereo durant les maintenances du site. Ces maintenaces sont planifiées à l'identique toutes
les semaines et sont codées en dur dans le plugin en attendant que ces plages ne soient renseignées dans la réponse de l'API

## 12/08/2024 V0.6 beta

Documentation OK

### Corrections
- Correction de 2 erreurs php

## 02/08/2024 V0.5 beta

> :warning: ***Important***  
> Il faut supprimer et recréer les équipements une fois cette mise à jour installée !

### Corrections
- Les utilisateurs avancés peuvent modifier les consignes de pH, Redox et chlore
- Syntaxe php : accolades utilisées systématiquement (`if` et `foreach`)
- Le token JWT est pris dans le corps de la réponse et plus dans l'entête
- Meilleure gestion du CapteurID dans les défauts concernant un capteur
- Utilisation du cache au lieu de l'utilisation de la table de config.

### Fonctionalités
- La commande 'AUTO' de la filtration est renommée en 'Plage'
- Log plus clairs (mais ça reste des logs d'une version bêta)
- Mise à jour API:
  - Passage à la version 392-W
  - Intégration des pompes à chaleur InoPac en plus des pompes à chaleur KlereoTherm
  - Prise en charge du défaut 61 "Défaut pompe à chaleur"

## 05/09/2023 V0.4 beta

### Corrections
- Les sorties dont le type n'est pas défini (null) sont ignorées

## 29/08/2023 V0.3 beta

> :warning: ***Important***  
> Il faut supprimer et recréer les équipements une fois cette mise à jour installée !

### Fonctionalités
- Lors de leur création, les commandes info et action prennent le nom personnalisé du site connect.klereo.com
- Une paire de commandes (action et info liée) est créée pour les consignes de pH (pour tous les utilisateurs) et les
autres consignes (Chlore, Redox, Température de chauffage) en fonction des droits de l'utilisateur
- Ajout des messages d'alarme spécifiques au firmware 2.08
- Gestion plus fine des sorties éclairage et auxiliaires (commandes off, on, minuterie, plages horaires)
- Ajout de la possibilité de paramétrer le temps de la minuterie

### Corrections
- L'unité des débits est maintenant m³/h sans code html
- Le changement de consigne pour une pompe à plusieurs vitesses est maintenant correctement interprété
- Le principe des logicalId des commandes a été revu et simplifié
- Les commandes "Temps de filtration total" et "Temps de chauffage total" ont une plage initiale de 0 à 5000 h
- La liste de choix pour la commande action du mode de chauffage est correctement gérée
- Si une unité contient litre, c'est un L majuscule qui est utilisé lors de la création
- La mesure de chlore est en mg/L

## 25/08/2023 V0.2 beta
- Les actions ont été revues :
  - Seuls l'éclairage, la filtration, le chauffage et les sorties auxiliaires présentes sont pilotables directement
  - La sortie chauffage est gérée
  - Les pompes de filtration à plusieurs vitesses sont gérées
- Si un message d'alerte est inconnu, le plugin affiche "Code alerte inconnu par le plugin : " suivi de la valeur du
code.

## 17/08/2023 V0.1 beta
- Version initiale :
  - Les commandes info des mesures en filtration et instantanées sont créées
  - Les commandes info des informations sur le bassin sont créées
  - Les commandes action et info liées aux actions sont créées en fonction des informations du bassin