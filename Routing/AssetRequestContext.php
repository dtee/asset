<?php
namespace Odl\AssetBundle\Routing;

use Symfony\Component\DependencyInjection\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

class AssetRequestContext
    extends RequestContext
{
    public function __construct(Container $container)
    {
        $this->container = $container;
        $request = $container->get('request');

        parent::__construct(
            $request->getBaseUrl(),
            $request->getMethod(),
            $request->getHost(),
            $request->getScheme(),
            $request->isSecure() ? $this->httpPort : $request->getPort(),
            $request->isSecure() ? $request->getPort() : $this->httpsPort
        );
    }
}