<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/vendor/autoload.php';
require_once(ABSPATH . 'wp-admin/includes/image.php');

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

add_action('wp_ajax_generate_file', 'generate_file_ajax_handler'); // wp_ajax_{action}
add_action('wp_ajax_nopriv_generate_file', 'generate_file_ajax_handler'); // wp_ajax_nopriv_{action}

function generate_file_ajax_handler()
{
    // Get all registrations
    global $wpdb;
    $post_id = $_GET['post_id'];

    $query = $wpdb->prepare(
        "
    SELECT DISTINCT p.*
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'registrations'
    AND p.post_status = 'publish'
    AND pm.meta_key = %s
    AND pm.meta_value = %s
    ",
        "registration_event_id",
        $post_id
    );

    $posts = $wpdb->get_results($query);

    // file information
    $target_dir = __DIR__ . "/";
    $event_name = slugify(get_the_title($post_id));
    $file_name = $event_name . "_inscriptions" . ".xlsx";
    $filePath = $target_dir . "/" . $file_name;

    if ($posts) {
        // write an excel file
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($filePath);

        $header_style = new Style();
        $header_style->setFontBold();

        $values = ['Nom', 'Prénom', 'Email', 'Téléphone'];
        $rowFromValues = Row::fromValues($values, $header_style);
        $writer->addRow($rowFromValues);


        foreach ($posts as $post) {
            $last_name = get_field("registration_last_name", $post->ID);
            $first_name = get_field("registration_first_name", $post->ID);
            $email = get_field("registration_email", $post->ID);
            $phone = get_field("registration_phone", $post->ID);

            $values = [$last_name, $first_name, $email, $phone];
            $rowFromValues = Row::fromValues($values);
            $writer->addRow($rowFromValues);
        }
    } else {
        wp_send_json_error("Aucune inscription pour l'instant.", 404);
    }

    $writer->close();



    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=$file_name");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($target_dir . $file_name));
    header("X-File-Name: $file_name");
    
    while (ob_get_level()) {
        ob_end_clean();
        @readfile($target_dir . $file_name);
    }

    unlink($target_dir . $file_name);

    wp_die();
}

function slugify($string)
{
    $string = preg_replace('/[^a-zA-Z0-9\s]+|\s+/', '_', $string);

    $string = strtolower($string);

    $string = trim($string, '_');
    $string = preg_replace('/_+/', '_', $string);

    return $string;
}
