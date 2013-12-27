<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

use ReflectionParameter;
use Ray\Aop\MethodInvocation;

interface SignalParameterInterface
{
    /**
     * Return single argument by signal
     *
     * @param ReflectionParameter $parameter
     * @param MethodInvocation    $invocation
     *
     * @return mixed
     */
    public function getArg(ReflectionParameter $parameter, MethodInvocation $invocation);

    /**
     * Attach parameter provider
     *
     * @param string                 $varName
     * @param ParamProviderInterface $provider
     *
     * @return $this
     */
    public function attachParamProvider($varName, ParamProviderInterface $provider);
}
