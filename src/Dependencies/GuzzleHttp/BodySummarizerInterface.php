<?php

namespace WPMedia\BackWPup\Dependencies\GuzzleHttp;

use WPMedia\BackWPup\Dependencies\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
