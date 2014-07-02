<?php
	
	/**
	* This template is used for posts that have an AdBuyout license.
	*/

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php wp_title(' - ',true,'right'); ?></title>

		<?php wp_head(); ?>

		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body class="wtt_takeover">
		<div id="wtt_relative">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

		<a href="<?php echo get_post_meta($post->ID, '_wtt_adbuyout_mobile_link', TRUE); ?>"><img src="<?php echo get_post_meta($post->ID, '_wtt_adbuyout_mobile_image_url', TRUE); ?>" alt="" id="mobile-banner-ad" /></a>
		<div class="wtt_takeover">
			<div id="wtt-container">
				<div id="wtt-row">
					<div id="wtt-content-col">
						
						<div id="wtt-meta">
							<h1><?php the_title(); ?></h1>
							<p>Post by <?php the_author(); ?> on <?php the_time('F j, Y'); ?></p>
						</div><!--wtt-meta-->

						<div id="wtt-content">
							<?php the_content(); ?>

							<p id="wtt-back"><a href="<?php the_permalink(); ?>?original=true">Go back to the original post on <?php bloginfo('name'); ?></a></p>
						</div><!--wtt-content-->

						<?php comments_template(); ?>

					
					</div><!--wtt-content-col-->

					<div id="wtt-ad-col">
						<?php echo get_post_meta($post->ID, '_wtt_adbuyout_sidebar_html', TRUE); ?>
					</div><!--wtt-ad-col-->

					<a href="<?php echo get_post_meta($post->ID, '_wtt_adbuyout_left_link', TRUE); ?>" id="wtt-click-left"></a>
					<a href="<?php echo get_post_meta($post->ID, '_wtt_adbuyout_right_link', TRUE); ?>" id="wtt-click-right"></a>
					<a href="<?php echo get_post_meta($post->ID, '_wtt_adbuyout_header_link', TRUE); ?>" id="wtt-click-top"></a>

				</div><!--wtt-row-->
			</div><!--wtt-container-->
			
		</div><!--wtt_takeover-->
		<?php endwhile; endif; ?>
		
		</div>
		<?php wp_footer(); ?>
	</body>
</html>