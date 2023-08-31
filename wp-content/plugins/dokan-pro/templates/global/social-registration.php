<div class="jssocials-shares">
<?php foreach ( $providers as $provider ) : ?>
    <div class="jssocials-share jssocials-share-<?php echo esc_attr( $provider ); ?>">
        <?php
        $query_args = [
            'vendor_social_reg' => $provider,
            'is_checkout'       => is_checkout(),
        ];
        ?>
        <a href="<?php echo add_query_arg( $query_args, $base_url ); ?>" class="jssocials-share-link">
            <i class="fab fa-<?php echo esc_attr( $provider ); ?> jssocials-share-logo"></i>
        </a>
    </div>
<?php endforeach; ?>
</div>
