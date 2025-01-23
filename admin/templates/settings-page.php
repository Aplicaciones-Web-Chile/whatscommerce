<?php
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'whatscommerce'));
}

// Guardar cambios si se envió el formulario
if (isset($_POST['submit'])) {
    check_admin_referer('whatscommerce_settings');
    update_option('whatscommerce_settings', $_POST['whatscommerce_settings']);
    echo '<div class="updated"><p>' . __('Configuración guardada.', 'whatscommerce') . '</p></div>';
}

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php
        settings_fields('whatscommerce_settings');
        do_settings_sections('whatscommerce');
        submit_button('Guardar cambios');
        ?>
    </form>
</div>
