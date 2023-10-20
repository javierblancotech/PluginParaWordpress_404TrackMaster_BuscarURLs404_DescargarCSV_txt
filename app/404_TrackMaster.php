<?php
/*
Plugin Name: 404 TrackMaster
Description: Registra las URLs que generan errores 404 y permite descargarlas en formatos CSV y TXT con filtrado por fechas.
Version: 1.5
Author: Javier Blanco
*/

// Función para encolar los estilos
function cargar_estilos_404_trackmaster() {
    wp_enqueue_style('estilo-404-trackmaster', plugin_dir_url(__FILE__) . 'estilo-404-trackmaster.css');
}
add_action('admin_enqueue_scripts', 'cargar_estilos_404_trackmaster');

add_action('template_redirect', 'capturar_404');

function capturar_404() {
    global $wpdb;

    if (is_404()) {
        $url_actual = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $tabla = $wpdb->prefix . 'urls_404';

        $existente = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE url = %s", $url_actual));

        if (!$existente) {
            $wpdb->insert($tabla, array('url' => $url_actual, 'fecha' => current_time('mysql')));
        }
    }
}

register_activation_hook(__FILE__, 'crear_tabla_404');

function crear_tabla_404() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'urls_404';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url varchar(255) NOT NULL,
        fecha datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id),
        KEY fecha (fecha)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('admin_post_exportar_csv_404', 'exportar_csv_404');

function exportar_csv_404() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'urls_404';
    $urls = $wpdb->get_results("SELECT url, fecha FROM $tabla ORDER BY fecha DESC", ARRAY_A);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=urls_404.csv');

    $fp = fopen('php://output', 'w');
    fputcsv($fp, array('URL', 'Fecha'));

    foreach ($urls as $url) {
        fputcsv($fp, $url);
    }

    fclose($fp);
    exit;
}

add_action('admin_post_exportar_txt_404', 'exportar_txt_404');

function exportar_txt_404() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'urls_404';
    $urls = $wpdb->get_results("SELECT url FROM $tabla ORDER BY fecha DESC", ARRAY_A);

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment;filename=urls_404.txt');

    foreach ($urls as $url) {
        echo $url['url'] . PHP_EOL;
    }
    exit;
}

add_action('admin_menu', 'menu_csv_404');

function menu_csv_404() {
    add_menu_page('404 TrackMaster', '404 TrackMaster', 'manage_options', 'exportar-404', 'pagina_exportar_404', 'dashicons-admin-tools');
}

function pagina_exportar_404() {
    ?>
    <div class="wrap">
        <h1>404 TrackMaster</h1>
        <p>
            Este plugin registra las URLs que generan errores 404 en tu sitio web. A continuación, puedes descargar un informe con todas esas URLs en formatos CSV y TXT. Los informes incluyen las URLs erróneas y, en el caso del CSV, la fecha en que se registró el error.
        </p>
        <a href="<?php echo admin_url('admin-post.php?action=exportar_csv_404'); ?>" class="button button-primary">Descargar CSV de URLs 404</a>
        <a href="<?php echo admin_url('admin-post.php?action=exportar_txt_404'); ?>" class="button button-primary">Descargar TXT de URLs 404</a>
    </div>
    <?php
}
?>
