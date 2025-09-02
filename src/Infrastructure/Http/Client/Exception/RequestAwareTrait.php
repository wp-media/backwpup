<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Client\Exception;

use Psr\Http\Message\RequestInterface;

trait RequestAwareTrait
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    private function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }
}
