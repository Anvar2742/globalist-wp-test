<?php

namespace Globalis\WP\Test;

define('REGISTRATION_ACF_KEY_LAST_NAME', 'field_64749cfff238e');
define('REGISTRATION_ACF_KEY_FIRST_NAME', 'field_64749d4bf238f');

add_filter('wp_insert_post_data', __NAMESPACE__ . '\\save_auto_title', 99, 2);
add_action('edit_form_after_title', __NAMESPACE__ . '\\display_custom_title_field');
add_action('transition_post_status', __NAMESPACE__ . '\\registration_publish', 10, 3 );

function save_auto_title($data, $postarr)
{
    if (! $data['post_type'] === 'registrations') {
        return $data;
    }
    if ('auto-draft' == $data['post_status']) {
        return $data;
    }

    if (!isset($postarr['acf'][REGISTRATION_ACF_KEY_LAST_NAME]) || !isset($postarr['acf'][REGISTRATION_ACF_KEY_FIRST_NAME])) {
        return $data;
    }

    $data['post_title'] = "#" . $postarr['ID'] .  " (" . $postarr['acf'][REGISTRATION_ACF_KEY_LAST_NAME] . " " . $postarr['acf'][REGISTRATION_ACF_KEY_FIRST_NAME] . ")";

    $data['post_name']  = wp_unique_post_slug(sanitize_title(str_replace('/', '-', $data['post_title'])), $postarr['ID'], $postarr['post_status'], $postarr['post_type'], $postarr['post_parent']);

    return $data;
}

function display_custom_title_field($post)
{
    if ($post->post_type !== 'registrations' || $post->post_status === 'auto-draft') {
        return;
    }
    ?>
    <h1><?= $post->post_title ?></h1>
    <?php
}

/**
 * Send email on new registration
 */
function registration_publish( $new_status, $old_status, $post ) {
    if ($post->post_type !== 'registrations' || $post->post_status === 'auto-draft') {
        return;
    }

    $event_id = get_field("registration_event_id", $post);
    $event_name = get_the_title($event_id);
    $event_ticket_id = get_field("event_pdf_entrance_ticket", $event_id);
    $event_ticket_pdf = array(wp_get_attachment_url($event_ticket_id));
    
    $registration_first_name = get_field("registration_first_name", $post);
    $registration_email = get_field("registration_email", $post);

    $separator = md5(time());
    $eol = PHP_EOL;

    $headers = 'From: Name <no-reply@globalis.localhost>' . "\r\n";
    $headers .= "MIME-Version: 1.0".$eol; 
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"".$eol;
    $headers .= "Content-Transfer-Encoding: 7bit".$eol;


    if ( $new_status == 'publish' && $old_status != 'publish' ) {
        $message = "Bonjour {$registration_first_name}, Vous vous êtes inscrit à l'événement $event_name. Votre ticket est joint à cet email.";
        wp_mail($registration_email, "Confirmation d'inscription - $event_name", $message, $headers, $event_ticket_pdf);
    }
}
