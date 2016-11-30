<?php
/**
 * @copyright: DotKernel
 * @library: dotkernel/dot-ems
 * @author: n3vrax
 * Date: 11/29/2016
 * Time: 7:50 PM
 */

namespace Dot\Ems\Factory;


use Dot\Ems\Exception\RuntimeException;
use Dot\Ems\Mapper\DbMapper;
use Interop\Container\ContainerInterface;
use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorInterface;
use Zend\Paginator\AdapterPluginManager;

/**
 * Class DbMapperFactory
 * @package Dot\Ems\Factory
 */
class DbMapperFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, $config = [])
    {
        if(!isset($config['adapter']) || isset($config['adapter']) && !is_string($config['adapter'])) {
            throw new RuntimeException('No db adapter specified');
        }

        if(!isset($config['table']) || isset($config['table']) && !is_string($config['table'])) {
            throw new RuntimeException('No table name specified');
        }

        if(!isset($config['entity_prototype'])) {
            throw new RuntimeException('No entity prototype specified');
        }

        $hydratorName = isset($config['entity_hydrator']) && is_string($config['entity_hydrator'])
            ? $config['entity_hydrator'] : null;

        //get entity prototype
        $entityPrototype = $this->getEntityPrototype($container, $config['entity_prototype']);
        if($hydratorName) {
            $hydrator = $this->getHydrator($container, $hydratorName);
        }
        else {
            $hydrator = new ClassMethods(false);
        }

        $mapper = new DbMapper(
            $config['table'],
            $container->get($config['adapter']),
            $entityPrototype, $hydrator);

        $mapper->setPaginatorAdapterManager($container->get(AdapterPluginManager::class));
        if(isset($config['paginator_adapter']) && is_string($config['paginator_adapter'])) {
            $mapper->setPaginatorAdapterName($config['paginator_adapter']);
        }

        if(isset($config['identifier_name']) && is_string($config['identifier_name'])) {
            $mapper->setIdentifierName($config['identifier_name']);
        }

        return $mapper;

    }

    protected function getEntityPrototype(ContainerInterface $container, $name)
    {
        $entityPrototype = $name;
        if($container->has($entityPrototype)) {
            $entityPrototype = $container->get($entityPrototype);
        }

        if(is_string($entityPrototype) && class_exists($entityPrototype)) {
            $entityPrototype = new $entityPrototype;
        }

        if(!is_object($entityPrototype)) {
            throw new RuntimeException('Entity prototype is not an object');
        }

        return $entityPrototype;
    }

    protected function getHydrator(ContainerInterface $container, $name)
    {
        $hydrator = $name;
        if($container->has($hydrator)) {
            $hydrator = $container->get($hydrator);
        }

        if(is_string($hydrator) && class_exists($hydrator)) {
            $hydrator = new $hydrator;
        }

        if(!$hydrator instanceof HydratorInterface) {
            throw new RuntimeException('Entity hydrator is not an instance of ' . HydratorInterface::class);
        }

        return $hydrator;
    }
}