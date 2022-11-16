<p><?php esc_html_e("BackWPup is dropping support for PHP versions less than 7.2. As such, using outdated and unsupported versions of PHP may expose your site to security vulnerabilities. Please update PHP to the latest version. Ask your hoster if you don't know how.", 'backwpup'); ?></p>
<p><?php echo wp_kses(
    __('For further information <a href="https://backwpup.com/docs/php-7-2-update/" target="_blank">see here</a>, and if any questions remain contact our support team.', 'backwpup'),
    ['a' => ['href' => true, 'target' => true]]
); ?></p>
