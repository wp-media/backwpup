<div
    class="notice<?php echo $bind->type ? ' ' . esc_attr($bind->type) : '' ?> notice-inpsyde"
    id="<?php echo esc_attr($bind->id) ?>_notice"
    data-notice-id="<?php echo esc_attr($bind->id) ?>"
>
    <div class="notice-inpsyde__content">
        <?php backwpup_template($bind, $bind->template) ?>
    </div>
    <?php if (isset($bind->buttonUrl) || isset($bind->dismissActionUrl)): ?>
        <p class="notice-inpsyde-actions">
            <?php if (isset($bind->buttonUrl)): ?>
                <a
                    class="button button--inpsyde"
                    href="<?php echo esc_url($bind->buttonUrl) ?>"
                    target="_blank"
                >
                    <?php echo esc_html($bind->buttonLabel) ?>
                </a>

            <?php endif ?>
            <?php if (isset($bind->dismissActionUrl)): ?>
                <a
                    class="button dismiss-button"
                    id="<?php echo esc_attr($bind->id) ?>_dismiss"
                    href="<?php echo esc_url($bind->dismissActionUrl) ?>"
                >
                    <?php echo esc_html_e('Don\'t show again', 'backwpup') ?>
                </a>
            <?php endif ?>
        </p>
    <?php endif ?>
</div>
