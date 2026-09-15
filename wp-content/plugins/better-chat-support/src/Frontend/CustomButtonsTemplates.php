<?php

namespace ThemeAtelier\BetterChatSupport\Frontend;

class CustomButtonsTemplates
{
   public static $getData;

   function __construct(array $args)
   {
      self::$getData = $args;
   }
   // Template Style 1
   public function mcs_button_s1()
	{
		$iterate_data = self::$getData;
		$atts         = $iterate_data;

		// button settings
		$agentPhoto       	= $atts['photo'];
		$background 		= $atts['background'];
		$hover_background 	= $atts['hover_background'];
		$text_color 		= $atts['text_color'];
		$hover_text_color 	= $atts['hover_text_color'];
		$border 			= $atts['border'];
		$border_style 		= $atts['border_style'];
		$border_color 		= $atts['border_color'];
		$border_hover_color = $atts['border_hover_color'];
		$icon_border 			= $atts['icon_border'];
		$icon_border_style 		= $atts['icon_border_style'];
		$icon_border_color 		= $atts['icon_border_color'];
		$icon_border_hover_color = $atts['icon_border_hover_color'];
		$padding 			= $atts['padding'];
		$top_label        	= $atts['top_label'];
		$main_label        	= $atts['main_label'];
		$onlineText        	= $atts['online'];
		$offline_text       = $atts['offline'];
		$fbid 			= $atts['fbid'];
		$visibility 		= $atts['visibility'];
		$buttonSizes      	= $atts['sizes'];
		$buttonRounded 		= $atts['border_radius'];
		$icon_buttonRounded 		= $atts['icon_border_radius'];
		$avlTimezone 		= $atts['timezone'];
		$avlSunday    		= $atts['sunday'];
		$avlMonday    		= $atts['monday'];
		$avlTuesday   		= $atts['tuesday'];
		$avlWednesday 		= $atts['wednesday'];
		$avlThursday  		= $atts['thursday'];
		$avlFriday    		= $atts['friday'];
		$avlSaturday  		= $atts['saturday'];

		$ch_settings = get_option('ch_settings');
		$open_in_new_tab = isset($ch_settings['open_in_new_tab']) ? $ch_settings['open_in_new_tab'] : '';
		$open_in_new_tab = $open_in_new_tab ? '_blank' : '_self';
		$url = 'https://www.m.me/' . esc_attr($fbid);
		?>
		<div class="button-wrapper"><div style="--mSupport-background: <?php echo esc_attr($background) ?>; --mSupport-hover-background: <?php echo esc_attr($hover_background) ?>; --mSupport-padding: <?php echo esc_attr($padding) ?>; --mSupport-btn-scale: <?php echo esc_attr($buttonSizes) ?>; --mSupport-border-radius: <?php echo esc_attr($buttonRounded); ?>; --mSupport-text-color: <?php echo esc_attr($text_color); ?>; --mSupport-text-hover-color: <?php echo esc_attr($hover_text_color); ?>; --mSupport-border: <?php echo esc_attr($border . ' ' . $border_style); ?>; --mSupport-border-color: <?php echo esc_attr($border_color); ?>; --mSupport-border-hover-color: <?php echo esc_attr($border_hover_color); ?>; --mSupport-icon-border: <?php echo esc_attr($icon_border . ' ' . $icon_border_style); ?>; --mSupport-icon-border-color: <?php echo esc_attr($icon_border_color); ?>; --mSupport-hover-icon-border-color: <?php echo esc_attr($icon_border_hover_color); ?>; --mSupport-icon-border-radius: <?php echo esc_attr($icon_buttonRounded); ?>;" <?php if ($avlTimezone) { ?> data-timezone="<?php echo esc_attr($avlTimezone); ?>" <?php } ?> class="mSupport_button shortcode_btn mSupport_button_advance mSupportButtons <?php echo esc_attr($visibility); ?> avatar-active" data-btnavailablety='{ "sunday":"<?php echo esc_attr($avlSunday); ?>", "monday":"<?php echo esc_attr($avlMonday); ?>", "tuesday":"<?php echo esc_attr($avlTuesday); ?>", "wednesday":"<?php echo esc_attr($avlWednesday); ?>", "thursday":"<?php echo esc_attr($avlThursday); ?>", "friday":"<?php echo esc_attr($avlFriday); ?>", "saturday":"<?php echo esc_attr($avlSaturday); ?>" }'><?php if ($agentPhoto) { ?><div><img src="<?php echo esc_attr($agentPhoto); ?>" /></div><?php } ?><div class="info-wrapper"><?php if ($top_label) { ?><div class="info"><?php echo esc_html($top_label); ?></div><?php } if ($main_label) { ?><div class="mSupport_title"><?php echo esc_html($main_label); ?></div><?php } if ($onlineText) { ?><div class="online"><?php echo esc_html($onlineText); ?></div><?php } if ($offline_text) { ?><div class="offline"><?php echo esc_html($offline_text); ?></div><?php } ?></div><?php echo '<div><a href="' . esc_attr($url) . '" target="' . esc_attr($open_in_new_tab) . '" class="chat-link"></a></div>'; ?></div></div><?php
	}

	// Template style 2
	public function mcs_button_s2()
	{
		$iterate_data = self::$getData;
		$atts         = $iterate_data;

		// button settings
		$background 		= $atts['background'];
		$hover_background 	= $atts['hover_background'];
		$text_color 		= $atts['text_color'];
		$hover_text_color 	= $atts['hover_text_color'];
		$icon_color 		= $atts['icon_color'];
		$hover_icon_color 	= $atts['hover_icon_color'];
		$icon_bg_color 		= $atts['icon_background'];
		$hover_icon_bg_color = $atts['hover_icon_background'];
		$border 			= $atts['border'];
		$border_style 		= $atts['border_style'];
		$border_color 		= $atts['border_color'];
		$border_hover_color = $atts['border_hover_color'];
		$icon_border 			= $atts['icon_border'];
		$icon_border_style 		= $atts['icon_border_style'];
		$icon_border_color 		= $atts['icon_border_color'];
		$icon_border_hover_color = $atts['icon_border_hover_color'];
		$padding 			= $atts['padding'];
		$main_label     	= $atts['main_label'];
		$fbid 				= $atts['fbid'];
		$visibility 		= $atts['visibility'];
		$buttonSizes   		= $atts['sizes'];
		$buttonRounded 		= $atts['border_radius'];
		$icon_buttonRounded = $atts['icon_border_radius'];
		$icon_bg = $atts['icon_bg'] === 'yes' ? 'icon_bg' : '';
		$icon = isset($atts['icon']) ? $atts['icon'] : '';
		$avlTimezone 		= $atts['timezone'];
		$avlSunday    		= $atts['sunday'];
		$avlMonday    		= $atts['monday'];
		$avlTuesday   		= $atts['tuesday'];
		$avlWednesday 		= $atts['wednesday'];
		$avlThursday  		= $atts['thursday'];
		$avlFriday    		= $atts['friday'];
		$avlSaturday  		= $atts['saturday'];

		$ch_settings = get_option('ch_settings');
		$open_in_new_tab = isset($ch_settings['open_in_new_tab']) ? $ch_settings['open_in_new_tab'] : '';
		$open_in_new_tab = $open_in_new_tab ? '_blank' : '_self';
		$url = 'https://www.m.me/' . esc_attr($fbid);
      ?>
		<div class="button-wrapper"><a style="--mSupport-background: <?php echo esc_attr($background) ?>; --mSupport-hover-background: <?php echo esc_attr($hover_background) ?>; --mSupport-padding: <?php echo esc_attr($padding) ?>; --mSupport-btn-scale: <?php echo esc_attr($buttonSizes) ?>; --mSupport-border-radius: <?php echo esc_attr($buttonRounded); ?>; --mSupport-text-color: <?php echo esc_attr($text_color); ?>; --mSupport-text-hover-color: <?php echo esc_attr($hover_text_color); ?>; --mSupport-icon-normal-color: <?php echo esc_attr($icon_color); ?>; --mSupport-icon-hover-color: <?php echo esc_attr($hover_icon_color); ?>; --mSupport-icon-normal-bg-color: <?php echo esc_attr($icon_bg_color); ?>; --mSupport-icon-hover-bg-color: <?php echo esc_attr($hover_icon_bg_color); ?>; --mSupport-border: <?php echo esc_attr($border . ' ' . $border_style); ?>; --mSupport-border-color: <?php echo esc_attr($border_color); ?>; --mSupport-border-hover-color: <?php echo esc_attr($border_hover_color); ?>; --mSupport-icon-border: <?php echo esc_attr($icon_border . ' ' . $icon_border_style); ?>; --mSupport-icon-border-color: <?php echo esc_attr($icon_border_color); ?>; --mSupport-hover-icon-border-color: <?php echo esc_attr($icon_border_hover_color); ?>; --mSupport-icon-border-radius: <?php echo esc_attr($icon_buttonRounded); ?>;" <?php if ($avlTimezone) { ?> data-timezone="<?php echo esc_attr($avlTimezone); ?>" <?php } ?> target="<?php echo esc_attr($open_in_new_tab) ?>" href="<?php echo esc_attr($url); ?>" class="mSupport_button shortcode_btn mSupportButtons chat_help_analytics <?php echo esc_attr($visibility); ?>" data-btnavailablety='{ "sunday":"<?php echo esc_attr($avlSunday); ?>", "monday":"<?php echo esc_attr($avlMonday); ?>", "tuesday":"<?php echo esc_attr($avlTuesday); ?>", "wednesday":"<?php echo esc_attr($avlWednesday); ?>", "thursday":"<?php echo esc_attr($avlThursday); ?>", "friday":"<?php echo esc_attr($avlFriday); ?>", "saturday":"<?php echo esc_attr($avlSaturday); ?>" }'><?php if ($icon == 'yes') { ?><span class="bubble__icon <?php echo esc_attr($icon_bg); ?>"><i class="icofont-facebook-messenger"></i></span><?php } echo esc_attr($main_label); ?></a></div>
		<?php
	}
} // End Class