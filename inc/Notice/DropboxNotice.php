<?php

namespace Inpsyde\BackWPup\Notice;

use BackWPup_Option;

class DropboxNotice extends Notice
{
    /**
     * @var string
     */
    public const OPTION_NAME = 'backwpup_notice_dropbox_needs_reauthenticated';
    /**
     * @var string
     */
    public const ID = self::OPTION_NAME;

    /**
     * List of jobs that need to be reauthenticated.
     *
     * @var array<int, string>
     */
    private $jobs = [];

    /**
     * {@inheritdoc}
     */
    protected function render(NoticeMessage $message): void
    {
        $this->view->warning($message, $this->getDismissActionUrl());
    }

    /**
     * {@inheritdoc}
     */
    protected function isScreenAllowed(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function shouldDisplay(): bool
    {
        if (!parent::shouldDisplay()) {
            return false;
        }

        $jobs = BackWPup_Option::get_job_ids();

        foreach ($jobs as $job) {
            $token = BackWPup_Option::get($job, 'dropboxtoken');
            if (is_array($token) && isset($token['access_token']) && !isset($token['refresh_token'])) {
                $name = BackWPup_Option::get($job, 'name');
                if (is_string($name)) {
                    $this->jobs[$job] = $name;
                }
            }
        }

        return !empty($this->jobs);
    }

    protected function message(): NoticeMessage
    {
        $message = new NoticeMessage('dropbox');
        $message->jobs = $this->jobs;

        return $message;
    }
}
