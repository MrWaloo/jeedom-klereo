# Changelog Klereo bêta

> :memo: ***Remarque***  
> Si une mise à jour du plugin en version bêta est disponible sans détails correspondants sur cette page, cela signifie
> que seule la documantation a été mise à jour.

## TODO
- Renommer la commande 'AUTO' de la filtration en 'Plage' et adapter la documentation en conséquence
- Permettre de modifier les plages horaires du mode 'Plage' pour les sorties qui peuvent être pilotées dans ce mode
- Rédiger la documentation (en cours)
- Revoir les messages de log
- Créer un widget

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