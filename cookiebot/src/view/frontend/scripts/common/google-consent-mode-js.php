<?php
/**
 * @var string $data_layer
 * @var string $url_passthrough
 * @var bool|string $consent_attribute
 * @var string $script_type
 */

?>
<script type="<?php echo esc_attr( $script_type ); ?>"<?php echo ! $consent_attribute ? '' : ' data-cookieconsent="' . esc_attr( $consent_attribute ) . '"'; ?>>
	window.<?php echo esc_js( $data_layer ); ?> = window.<?php echo esc_js( $data_layer ); ?> || [];

	function gtag() {
		<?php echo esc_js( $data_layer ); ?>.push(arguments);
	}

	gtag("consent", "default", {
		ad_personalization: "denied",
		ad_storage: "denied",
		ad_user_data: "denied",
		analytics_storage: "denied",
		functionality_storage: "denied",
		personalization_storage: "denied",
		security_storage: "granted",
		wait_for_update: 500,
	});
	gtag("set", "ads_data_redaction", true);
	<?php
	if ( $url_passthrough ) {
		echo /** @lang JavaScript */
			'gtag("set", "url_passthrough", true);' . PHP_EOL;
	}
	?>
</script>
