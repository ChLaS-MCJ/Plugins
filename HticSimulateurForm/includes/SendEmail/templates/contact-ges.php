<?php
/**
 * Template email pour GES - formulaire de contact
 * Fichier: includes/SendEmail/templates/contact-ges.php
 */
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #222F46; color: white; padding: 25px; text-align: center; }
        .header h2 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .section { background: #f9f9f9; border-left: 4px solid #E39411; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
        .info-label { flex: 0 0 150px; font-weight: bold; color: #666; }
        .info-value { flex: 1; color: #333; }
        .message-box { background: #fff; border: 2px solid #82C720; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        .action-box { background: #fff3cd; border-left: 4px solid #E39411; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📧 NOUVEAU MESSAGE DE CONTACT</h2>
            <p style="margin: 10px 0 0; font-size: 14px;">Reçu le <?php echo $timestamp; ?></p>
        </div>
        
        <div class="content">
            <!-- Objet de la demande -->
            <div class="section">
                <h3 style="margin-top: 0; color: #222F46;">📋 Objet de la demande</h3>
                <p style="font-size: 18px; margin: 0;"><strong><?php echo htmlspecialchars($contact['objet']); ?></strong></p>
            </div>
            
            <!-- Message du client -->
            <div class="message-box">
                <h3 style="margin-top: 0; color: #222F46;">💬 Message</h3>
                <div style="line-height: 1.6; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($contact['message'])); ?></div>
            </div>
            
            <!-- Informations client -->
            <div class="section" style="border-left-color: #222F46;">
                <h3 style="margin-top: 0; color: #222F46;">👤 Informations du contact</h3>
                <div class="info-row">
                    <span class="info-label">Nom complet :</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email :</span>
                    <span class="info-value"><a href="mailto:<?php echo htmlspecialchars($client['email']); ?>"><?php echo htmlspecialchars($client['email']); ?></a></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone :</span>
                    <span class="info-value"><strong style="color: #E39411;"><?php echo htmlspecialchars($client['telephone']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ville :</span>
                    <span class="info-value"><?php echo htmlspecialchars(($contact['code_postal'] ?? '') . ' ' . ($contact['ville'] ?? '')); ?></span>
                </div>
                <?php if (!empty($contact['adresse'])): ?>
                <div class="info-row">
                    <span class="info-label">Adresse :</span>
                    <span class="info-value"><?php echo htmlspecialchars($contact['adresse']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($documents['fichier'])): ?>
            <div class="section" style="border-left-color: #82C720;">
                <h3 style="margin-top: 0; color: #222F46;">📎 Pièce jointe</h3>
                <p>Le client a joint un fichier : <strong><?php echo htmlspecialchars($documents['fichier']['name'] ?? 'Document'); ?></strong></p>
            </div>
            <?php endif; ?>
            
            <!-- Action requise -->
            <div class="action-box">
                <h3 style="margin-top: 0; color: #E39411;">⚠️ ACTION REQUISE</h3>
                <ul style="margin: 10px 0;">
                    <li>Répondre au client sous 48h maximum</li>
                    <li>Traiter la demande selon sa nature</li>
                    <li>Mettre à jour le CRM</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>Email automatique - Formulaire de contact GES Solutions<br>
            Ne pas répondre à cet email - Utiliser l'email du client</p>
        </div>
    </div>
</body>
</html>