<?php
/**
 * Template email de confirmation pour le client - formulaire de contact
 * Fichier: includes/SendEmail/templates/contact-client.php
 */
?>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #222F46, #3a4f66); color: white; padding: 40px 30px; text-align: center; }
        .content { padding: 40px 30px; }
        .message-recap { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #82C720; }
        .cta-box { background: #e8f5e8; padding: 25px; border-radius: 8px; text-align: center; margin: 30px 0; }
        .footer { background: #222F46; color: white; padding: 30px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">Merci pour votre message !</h1>
            <p style="margin: 15px 0 0; font-size: 16px; opacity: 0.9;">Nous avons bien reçu votre demande</p>
        </div>
        
        <div class="content">
            <p style="font-size: 16px; line-height: 1.6;">
                Bonjour <strong><?php echo htmlspecialchars($client['prenom'] ?? ''); ?></strong>,
            </p>
            
            <p style="font-size: 16px; line-height: 1.6;">
                Nous vous confirmons la bonne réception de votre message concernant :
            </p>
            
            <div class="message-recap">
                <h3 style="margin-top: 0; color: #222F46;">📋 <?php echo htmlspecialchars($contact['objet']); ?></h3>
                <p style="color: #666; line-height: 1.6;">
                    <?php 
                    $message = $contact['message'] ?? '';
                    echo nl2br(htmlspecialchars(substr($message, 0, 200)));
                    echo strlen($message) > 200 ? '...' : '';
                    ?>
                </p>
            </div>
            
            <div class="cta-box">
                <h3 style="color: #222F46; margin-top: 0;">⏱️ Nous vous répondons sous 48h</h3>
                <p style="color: #666; margin: 10px 0;">
                    Notre équipe traite votre demande et vous recontactera dans les plus brefs délais.
                </p>
                <p style="color: #E39411; font-weight: bold; margin: 15px 0 0;">
                    En cas d'urgence : 01 23 45 67 89
                </p>
            </div>
            
            <p style="font-size: 14px; color: #666; line-height: 1.6;">
                Ce message est un accusé de réception automatique. Merci de ne pas y répondre.<br>
                Notre équipe vous contactera directement à l'adresse email que vous nous avez communiquée.
            </p>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 10px; font-size: 16px;">GES Solutions</p>
            <p style="margin: 0; font-size: 12px; opacity: 0.8;">
                Expert en solutions énergétiques<br>
                www.ges-solutions.fr
            </p>
        </div>
    </div>
</body>
</html>