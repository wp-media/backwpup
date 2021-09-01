<p><?php _e('You have one or more BackWPup jobs that need to reauthenticate with Dropbox.', 'backwpup') ?></p>
<p><?php _e('The Dropbox API is discontinuing long-lived access tokens. To conform to these new changes, we must implement the use of refresh tokens, which can only be fetched when you reauthenticate.', 'backwpup') ?></p>
<p><?php _e('Please visit each job below and reauthenticate your Dropbox connection.', 'backwpup') ?></p>

<ul>
    <?php foreach ($bind->jobs as $id => $name): ?>
        <li>
            <a href="<?php echo wp_nonce_url(add_query_arg([
                'page' => 'backwpupeditjob',
                'tab' => 'dest-dropbox',
                'jobid' => $id,
            ], network_admin_url('admin.php')), 'edit-job') ?>">
                <?php echo esc_html($name) ?>
            </a>
        </li>
    <?php endforeach ?>
</ul>
