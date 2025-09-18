<?php
/**
 * Base Template :
 * 
 */


function render_email_base($title, $content, $client) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo esc_html($title); ?></title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #0073aa; color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px 20px; background: #f9f9f9; }
            .result-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .result-box h3 { color: #0073aa; margin-top: 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; background: #f0f0f0; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { padding: 12px 8px; text-align: left; border-bottom: 1px solid #eee; }
            th { background-color: #f8f9fa; font-weight: 600; }
            .highlight { color: #0073aa; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?php echo get_bloginfo('name'); ?></h1>
                <p><?php echo esc_html($title); ?></p>
            </div>
            
            <div class="content">
                <h2>Bonjour <?php echo esc_html($client['prenom'] . ' ' . $client['nom']); ?>,</h2>
                
                <?php echo $content; ?>
                
                <p style="margin-top: 30px;">Cette simulation a été générée le <?php echo date('d/m/Y à H:i'); ?>.</p>
                <p>Pour toute question, n'hésitez pas à nous contacter.</p>
            </div>
            
            <div class="footer">
                <p><?php echo get_bloginfo('name'); ?> - <?php echo home_url(); ?></p>
                <p>Email généré automatiquement</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}