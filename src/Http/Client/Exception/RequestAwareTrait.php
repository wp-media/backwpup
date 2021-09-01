<?php

namespace Inpsyde\BackWPup\Http\Client\Exception;

use Psr\Http\Message\RequestInterface;

trait RequestAwareTrait
{
    /**
     * @var RequestInterface
     */
    private $request;

    private function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->request;
    }
}
