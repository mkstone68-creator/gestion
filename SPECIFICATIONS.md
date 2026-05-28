# 📘 Cahier des Charges Fonctionnel : Système de Gestion Scolaire (SGS)

Ce document définit les spécifications fonctionnelles et la matrice de contrôle d'accès pour l'application Web de gestion scolaire et de suivi des présences. L'application repose sur une architecture à trois niveaux de rôles : **Administrateur**, **Enseignant** et **Étudiant**.

---

## 🏛️ 1. Profil : ADMINISTRATEUR (Super-Utilisateur)
L'Administrateur dispose d'une visibilité et d'une autorité de contrôle absolues sur l'ensemble de la plateforme.

### 🔧 Gestion & Paramétrage du Référentiel (CRUD)
*   **Utilisateurs & Accès :** Création, modification, suspension de comptes et attribution des rôles.
*   **Effectifs :** Gestion complète des fiches élèves et enseignants.
*   **Structure Scolaire :** Configuration des classes, répartition des cohortes ("armoires de classe").
*   **Parcours Académique :** Définition des formations, des sessions de cours et des calendriers.
*   **Documents Officiels :** Paramétrage et délivrance des modèles d'attestation.

### 📊 Pilotage & Statistiques en Temps Réel
*   **Dashboard Décisionnel :** Tableau de bord dynamique consolidant le nombre total d'élèves, d'enseignants, de classes et de parcours de formation.
*   **Suivi Opérationnel :** Monitoring global de la capacité des classes (places disponibles, taux d'occupation).

### 🧾 Module Attestations & Certification
*   Génération automatique des attestations de réussite et certificats de scolarité.
*   Processus de validation électronique des documents.
*   Exportation multi-format (Téléchargement PDF, impression directe).

---

## 👨‍🏫 2. Profil : ENSEIGNANT (Gestion Pédagogique)
L'Enseignant orchestre les activités pédagogiques et le suivi d'assiduité des classes qui lui sont affectées.

### 📋 Supervision Pédagogique
*   Accès à la liste des classes et élèves sous sa responsabilité.
*   Consultation des programmes et formations assignés.

### 📆 Contrôle de l'Assiduité (Présences & Absences)
*   **Feuille d'émargement numérique :** Enregistrement des présences et signalement des absences en temps réel.
*   **Historique :** Consultation et édition des registres d'assiduité passés.
*   **Justificatifs :** Premier niveau de validation ou de traitement des motifs d'absence (selon le workflow interne).

### 📈 Analyse de la Progression
*   Suivi de la courbe d'assiduité individuelle et collective des élèves.
*   Accès aux statistiques de présence sous forme visuelle pour identifier le décrochage scolaire.

---

## 🎓 3. Profil : ÉTUDIANT (Espace Personnel & Participation)
L'Étudiant accède à un espace personnel sécurisé, centré sur son propre parcours de formation.

### 👤 Espace Personnel & Scolarité
*   Consultation de son profil académique et des informations personnelles.
*   Visualisation de sa classe, de l'emploi du temps, de sa formation et de la session en cours.

### 📅 Gestion Active de la Présence
*   **Check-in Interactif :** Possibilité de marquer sa présence (si activé par le système, par exemple via QR code ou géolocalisation locale).
*   **Mon Registre :** Consultation transparente de son historique personnel d'assiduité.

### ❌ Gestion des Justificatifs d'Absence
*   Soumission de demandes d'absence planifiée.
*   Dépôt en ligne de pièces justificatives (certificat médical, etc.) pour régularisation.

### 📄 Coffre-fort Numérique (Attestations)
*   Visualisation des attestations validées par l'administration.
*   Téléchargement et impression autonomes des documents validés.

---

## 🔄 4. Principes Directeurs & Logique Système

### 🔐 1. Sécurité et Cloisonnement des Rôles
Chaque utilisateur est authentifié de manière unique et est redirigé vers une interface strictement adaptée à son rôle. Aucune fuite de données ou manipulation croisée non autorisée n'est possible.

### ⚙️ 2. Principe de Saisie Unique et Centralisation
Les données (élèves, enseignants, classes) sont saisies **une seule fois** par l'administrateur. Elles se propagent ensuite automatiquement dans les espaces Enseignants et Étudiants concernés, garantissant une cohérence parfaite de la base de données.

### ⚡ 3. Dynamisme et Temps Réel
Toutes les actions d'émargement et d'enregistrement mettent à jour instantanément les dashboards administratifs et les synthèses statistiques.

---

## 💡 Matrice de Synthèse Rapide

| Fonctionnalité | 🏛️ Administrateur | 👨‍🏫 Enseignant | 🎓 Étudiant |
| :--- | :---: | :---: | :---: |
| **Gestion des Comptes & Droits** | 🟢 *Contrôle total* | 🔴 *Aucun accès* | 🔴 *Aucun accès* |
| **Configuration des Classes & Formations** | 🟢 *Contrôle total* | 🟡 *Lecture seule* | 🔴 *Aucun accès* |
| **Saisie des Présences** | 🟢 *Supervision/Édition* | 🟢 *Saisie active* | 🟡 *Check-in perso uniquement* |
| **Demande / Justification d'Absence** | 🟢 *Validation finale* | 🟡 *Avis/Suivi* | 🟢 *Soumission & dépôt* |
| **Émission d'Attestations** | 🟢 *Création & Validation* | 🔴 *Aucun accès* | 🟡 *Téléchargement (validées)* |
| **Statistiques & Dashboards** | 🟢 *Vision globale* | 🟡 *Vision par classe* | 🟡 *Vision personnelle* |
