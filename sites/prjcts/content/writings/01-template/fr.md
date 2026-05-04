# Modèle — à supprimer ou remplacer

Entrée d'exemple pour la collection **writings**. Dépose ton texte dans `nl.md`, `en.md`, `fr.md`. Markdown réel fonctionne — Parsedown le rend en HTML.

## Markdown

- Listes
- *italique*, **gras**, `code`
- [Liens](https://prjcts.be)
- Notes de bas de page via HTML inline si besoin

> Les citations s'affichent avec une bordure rouge à gauche.

## Aside

Optionnel : dépose un `aside_nl.md` (en/fr) pour les notes, bibliographie ou contexte. Il apparaît sous l'article principal avec un séparateur fin.

## Médias

Dépose images, vidéos, audio ou PDF dans ce dossier — ils apparaissent automatiquement en bas.

## Méta

Dans `meta.json` :
- `type` — `essay` / `story` / `research` / `letter`
- `date` — format ISO (`2026-05-03`) ; tri décroissant
- `title_nl/en/fr` + `subtitle_nl/en/fr` — par langue
