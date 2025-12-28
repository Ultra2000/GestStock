Guide d'Implémentation : Intégrations Fiscales & Sociales (PPF & URSSAF)

Ce document sert de référence pour l'implémentation des services de communication externe.
1. Architecture Technique & Modèles

    Multi-tenancy : Utilisation de company_id pour isoler les données.

    Sécurité : Les credentials API sont chiffrés dans company_integrations (JSON credentials).

    Modèle Sale :

        status : pending -> completed.

        security_hash : Chaînage SHA-256 pour conformité fiscale.

        ppf_status : À ajouter (string, nullable).

2. Intégration PPF (Factur-X) 🚀
A. Générateur Factur-X (App\Services\FacturXGenerator)

Objectif : Transformer une Sale en PDF hybride (PDF/A-3 + XML CII). Instructions pour l'IA :

    Utiliser les données du modèle Sale (vendeur, client, lignes d'articles).

    Mapper les champs vers le standard CII (Cross Industry Invoice).

    Injecter le XML dans le PDF avec les métadonnées obligatoires (factur-x.xml).

    Stocker le résultat dans le disk private ou s3.

B. Service de Transmission (App\Services\PpfService)

Objectif : Communiquer avec l'API PISTE (Sandbox Chorus Pro). Endpoints de Qualification :

    Auth : https://api.piste.gouv.fr/cas/oauth2/v2.0/token

    Dépôt : POST /rechercherStructure puis POST /soumettreFacture. Logique de test (Matelas de données) :

    Émetteur (PME) : SIRET 35068473658377.

    Récepteur (Client) : SIRET 46096855178036.

3. Intégration URSSAF (Suivi de Dette) 🏥
A. Service de Données (App\Services\UrssafService)

Objectif : Récupérer la situation de compte de l'entreprise. Auth : OAuth2 via le portail développeur URSSAF. Actions :

    getAccountSituation() : Appeler l'API pour récupérer le solde actuel et les échéances.

    getVigilanceCertificate() : Permettre le téléchargement de l'attestation de vigilance.

B. Widget Filament (App\Filament\Widgets\UrssafOverviewWidget)

Objectif : Affichage Dashboard. Contenu :

    Card 1 : "Solde URSSAF" (Montant en €).

    Card 2 : "Prochaine Échéance" (Date + Montant).

    Card 3 : "Conformité" (Status des attestations).

🛠️ Instructions de Codage pour Gemini/Copilot

    "En utilisant le fichier PROMPT_ENGINEERING_ERP.md :

        Génère la migration pour ajouter ppf_status et ppf_id à la table sales.

        Crée la classe FacturXGenerator.php. Assure-toi que le XML généré respecte le profil 'BASIC'.

        Implémente le PpfService.php avec la gestion du Token OAuth2 (cache le token pour 1 heure).

        Dans SaleResource.php de Filament, crée une Action nommée 'Envoyer au PPF' qui appelle le service. Elle doit être désactivée si status != completed.

        Crée le UrssafOverviewWidget.php en utilisant le UrssafService pour peupler les données."

📝 Journal des Tests (Qualification)
Service	Action	Résultat Attendu
PPF	Soumettre Facture	Retourne idStructureCPP et statut DÉPOSÉE.
URSSAF	Get Situation	Retourne un JSON avec les dettes du SIRET de test