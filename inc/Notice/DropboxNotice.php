<?php

namespace Inpsyde\BackWPup\Notice;

use BackWPup_Option;

/**
 * Class DropboxNotice
 *
 * @package Inpsyde\BackWPup\Notice
 */
class DropboxNotice extends Notice
{
    const OPTION_NAME = 'backwpup_notice_dropbox_needs_reauthenticated';
    const ID = self::OPTION_NAME;

    /**
     * List of jobs that need to be reauthenticated
     *
     * @var array
     */
    private $jobs = [];

    /**
     * {@inheritdoc}
     */
    protected function render(NoticeMessage $message)
    {
        $this->view->warning($message, $this->getDismissActionUrl());
    }

/**
 * {@inheritdoc}
 */
    protected function isScreenAllowed()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function shouldDisplay()
    {
        if (!parent::shouldDisplay()) {
            return false;
        }

        $jobs = BackWPup_Option::get_job_ids();

        foreach ($jobs as $job) {
            $token = BackWPup_Option::get($job, 'dropboxtoken');
            if (isset($token['access_token']) && !isset($token['refresh_token'])) {
                $this->jobs[$job] = BackWPup_Option::get($job, 'name');
            }
        }

        return !empty($this->jobs);
    }

    protected function message()
    {
        $message = new NoticeMessage('dropbox');
        $message->jobs = $this->jobs;

        return $message;
    }
}
