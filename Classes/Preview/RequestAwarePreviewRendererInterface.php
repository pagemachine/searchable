<?php
namespace PAGEmachine\Searchable\Preview;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use Psr\Http\Message\ServerRequestInterface;

interface RequestAwarePreviewRendererInterface
{
    public function setRequest(ServerRequestInterface $request): void;
}
