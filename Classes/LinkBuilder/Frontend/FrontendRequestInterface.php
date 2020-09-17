<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\LinkBuilder\Frontend;

use Psr\Http\Message\UriInterface;

interface FrontendRequestInterface
{
    public function send(UriInterface $baseUri, array $linkConfigurations): array;
}
