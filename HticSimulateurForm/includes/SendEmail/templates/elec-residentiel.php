<?php
/**
 * Template Email Électricité Résidentielle - Version simplifiée
 * includes/SendEmail/templates/elec-residentiel.php
 */

// Inclure le template de base
require_once __DIR__ . '/base-template.php';

$title = 'Votre simulation électricité GES Solutions';

// Préparer les données client pour le template de base
$client = [
    'nom' => $data['client']['nom'] ?? '',
    'prenom' => $data['client']['prenom'] ?? ''
];

// Contenu simplifié
ob_start();
?>

<div class="result-box" style="background-color: #f8f9fa; border-left: 4px solid #222F46;">
    <h3 style="color: #222F46;">✅ Simulation bien reçue !</h3>
    <p>Nous avons bien reçu votre demande de simulation pour votre contrat d'électricité.</p>
    
    <p style="margin-top: 20px;">
        <strong style="font-size: 18px; color: #222F46;">Votre estimation mensuelle :</strong><br>
        <span style="font-size: 32px; color: #82C720; font-weight: bold;">
            <?php echo number_format($data['results']['estimation_mensuelle'] ?? 0, 0, ',', ' '); ?> €
        </span>
        <span style="font-size: 14px; color: #666;">TTC/mois</span>
    </p>
</div>

<div class="result-box">
    <h3 style="color: #E39411;">📄 Votre document personnalisé</h3>
    <p><strong>Vous trouverez en pièce jointe le PDF complet</strong> contenant :</p>
    <ul style="color: #666;">
        <li>Le détail de votre simulation personnalisée</li>
        <li>Les caractéristiques de votre logement</li>
        <li>Votre consommation estimée</li>
        <li>Les différentes options tarifaires</li>
    </ul>
    <p style="background-color: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 15px;">
        💡 <strong>Conservez ce document</strong> pour votre suivi et nos échanges futurs
    </p>
</div>

<div class="result-box" style="background-color: #e8f5e8; border-left: 4px solid #82C720;">
    <h3 style="color: #222F46;">📞 Prochaine étape</h3>
    <p style="font-size: 16px;">
        <strong>Un conseiller GES Solutions vous contactera sous 72h</strong>
    </p>
    <p>Il pourra :</p>
    <ul>
        <li>Répondre à toutes vos questions</li>
        <li>Affiner votre simulation si nécessaire</li>
        <li>Vous accompagner dans la souscription</li>
        <li>Planifier la mise en service</li>
    </ul>
</div>

<div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
    <p style="margin: 0; color: #666;">Une question urgente ?</p>
    <p style="margin: 5px 0; font-size: 20px; color: #222F46; font-weight: bold;">
        📞 01 23 45 67 89
    </p>
    <p style="margin: 0; color: #666; font-size: 12px;">
        Du lundi au vendredi - 9h à 18h
    </p>
</div>

<p style="margin-top: 20px; font-size: 12px; color: #999; text-align: center;">
    Merci de votre confiance. L'équipe GES Solutions
</p>

<?php
$content = ob_get_clean();

// Générer l'email complet avec le template de base
echo render_email_base($title, $content, $client, false);
?>