# Changelog Klereo bêta

## TODO
- Rédiger la documentation
- Revoir les messages de log
- Créer un widget

## xx/08/2023 V0.3 beta

###Fonctionalités
- Lors de leur création, les commandes info et action prennent le nom personnalisé via le site connect.klereo.com
- Une paire de commandes (action et info liée) est créée pour les consignes de pH (pour tous les utilisateurs) et les autres consignes (Chlore, Redox, Température de chauffage) en fonction des droits de l'utilisateur

### Corrections
- L'unité des débits est maintenant m³/h sans code html
- Le changement de consigne pour une pompe à plusieurs vitesses est maintenant correctement interprété
- Le principe des logicalId des commandes action a été revu et simplifié
- Les commandes "Temps de filtration total" et "Temps de chauffage total" ont une plage initiale de 0 à 5000 h
- La liste de choix pour la commande action du mode de chauffage est correctement gérée
- La mesure de chlore est mg/L
- Si une unité contient litre, c'est un L majuscule qui est utilisé lors de la création

## 25/08/2023 V0.2 beta
- Les actions ont été revues :
  - Seuls l'éclairage, la filtration, le chauffage et les sorties auxiliaires présentes sont pilotables directement
  - La sortie chauffage est gérée
  - Les pompes de filtration à plusieurs vitesses sont gérées
- Si un message d'alerte est inconnu, le plugin affiche "Code alerte inconnu par le plugin : " suivi de la valeur du code.

## 17/08/2023 V0.1 beta
- Version initiale :
  - Les commandes info des mesures en filtration et instantanées sont créées
  - Les commandes info des informations sur le bassin sont créées
  - Les commandes action et info liées aux actions sont créées en fonction des informations du bassin