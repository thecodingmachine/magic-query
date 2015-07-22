<?php

namespace Mouf\Database\QueryWriter\Utils;

use Mouf\MoufInstanceDescriptor;

/**
 * This class is used to find conditions recursively inside a MoufInstanceDescriptor.
 */
class FindParametersService
{
    /**
     * Tries to find parameters recursively in the instances and retrieves the list of parameters found.
     *
     * @param MoufInstanceDescriptor $instanceDescriptor
     *
     * @return string[]
     */
    public static function findParameters(MoufInstanceDescriptor $instanceDescriptor)
    {
        return array_keys(self::recursiveFindParameters($instanceDescriptor)[1]);
    }

    /**
     * @param MoufInstanceDescriptor $instanceDescriptor
     * @param array<string>          $visitedInstances   The list of names of visited instances.
     */
    private static function recursiveFindParameters(MoufInstanceDescriptor $instanceDescriptor, array $visitedInstances = array(), array $foundParameters = array())
    {
        if (isset($visitedInstances[$instanceDescriptor->getIdentifierName()])) {
            return array($visitedInstances, $foundParameters);
        }
        $visitedInstances[$instanceDescriptor->getIdentifierName()] = true;

        if ($instanceDescriptor->getClassDescriptor()->getName() == 'SQLParser\\Node\\Parameter') {
            $foundParameters[$instanceDescriptor->getProperty('name')->getValue()] = true;

            return array($visitedInstances, $foundParameters);
        }
        if ($instanceDescriptor->getClassDescriptor()->getName() == 'Mouf\\Database\\QueryWriter\\Condition\\ParamAvailableCondition'
                || $instanceDescriptor->getClassDescriptor()->getName() == 'Mouf\\Database\\QueryWriter\\Condition\\ParamEqualsCondition'
                || $instanceDescriptor->getClassDescriptor()->getName() == 'Mouf\\Database\\QueryWriter\\Condition\\ParamNotAvailableCondition') {
            $foundParameters[$instanceDescriptor->getProperty('parameterName')->getValue()] = true;

            return array($visitedInstances, $foundParameters);
        }

        $classDescriptor = $instanceDescriptor->getClassDescriptor();
        /* @var $classDescriptor MoufReflectionClass */
        foreach ($classDescriptor->getInjectablePropertiesByConstructor() as $moufPropertyDescriptor) {
            /* @var $moufPropertyDescriptor MoufPropertyDescriptor */
            $name = $moufPropertyDescriptor->getName();

            $value = $instanceDescriptor->getConstructorArgumentProperty($name)->getValue();
            if ($value instanceof MoufInstanceDescriptor) {
                list($visitedInstances, $foundParameters) = self::recursiveFindParameters($value, $visitedInstances, $foundParameters);
            } elseif (is_array($value)) {
                foreach ($value as $val) {
                    if ($val instanceof MoufInstanceDescriptor) {
                        list($visitedInstances, $foundParameters) = self::recursiveFindParameters($val, $visitedInstances, $foundParameters);
                    }
                }
            }
        }

        foreach ($classDescriptor->getInjectablePropertiesBySetter() as $moufPropertyDescriptor) {
            /* @var $moufPropertyDescriptor MoufPropertyDescriptor */
            $name = $moufPropertyDescriptor->getName();

            $value = $instanceDescriptor->getSetterProperty($name)->getValue();
            if ($value instanceof MoufInstanceDescriptor) {
                list($visitedInstances, $foundParameters) = self::recursiveFindParameters($value, $visitedInstances, $foundParameters);
            } elseif (is_array($value)) {
                foreach ($value as $val) {
                    if ($val instanceof MoufInstanceDescriptor) {
                        list($visitedInstances, $foundParameters) = self::recursiveFindParameters($val, $visitedInstances, $foundParameters);
                    }
                }
            }
        }

        foreach ($classDescriptor->getInjectablePropertiesByPublicProperty() as $moufPropertyDescriptor) {
            /* @var $moufPropertyDescriptor MoufPropertyDescriptor */
            $name = $moufPropertyDescriptor->getName();

            $value = $instanceDescriptor->getInjectablePropertiesByPublicProperty($name)->getValue();
            if ($value instanceof MoufInstanceDescriptor) {
                list($visitedInstances, $foundParameters) = self::recursiveFindParameters($value, $visitedInstances, $foundParameters);
            } elseif (is_array($value)) {
                foreach ($value as $val) {
                    if ($val instanceof MoufInstanceDescriptor) {
                        list($visitedInstances, $foundParameters) = self::recursiveFindParameters($val, $visitedInstances, $foundParameters);
                    }
                }
            }
        }

        return array($visitedInstances, $foundParameters);
    }
}
