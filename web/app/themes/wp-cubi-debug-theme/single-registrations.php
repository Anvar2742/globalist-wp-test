<!doctype html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php wp_head(); ?>
    </head>
    <?php
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 ); 
        exit();
    ?>
    <body>
        <h1><?php the_title(); ?>
    </body>
</html>
