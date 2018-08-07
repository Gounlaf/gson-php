<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Tebru\Gson\Internal;

use ReflectionProperty;
use Tebru\AnnotationReader\AnnotationCollection;
use Tebru\Gson\Annotation\Exclude;
use Tebru\Gson\Annotation\Expose;
use Tebru\Gson\Annotation\Since;
use Tebru\Gson\Annotation\Until;
use Tebru\Gson\ClassMetadata;
use Tebru\Gson\ExclusionData;
use Tebru\Gson\ExclusionStrategy;
use Tebru\Gson\PropertyMetadata;

/**
 * Class Excluder
 *
 * @author Nate Brunette <n@tebru.net>
 */
final class Excluder
{
    /**
     * Version, if set, will be used with [@see Since] and [@see Until] annotations
     *
     * @var string
     */
    private $version;

    /**
     * Which modifiers are excluded
     *
     * By default only static properties are excluded
     *
     * @var int
     */
    private $excludedModifiers = ReflectionProperty::IS_STATIC;

    /**
     * If this is true, properties will need to explicitly have an [@see Expose] annotation
     * to be serialized or deserialized
     *
     * @var bool
     */
    private $requireExpose = false;

    /**
     * Exclusions strategies during serialization
     *
     * @var ExclusionStrategy[]
     */
    private $serializationStrategies = [];

    /**
     * Exclusion strategies during deserialization
     *
     * @var ExclusionStrategy[]
     */
    private $deserializationStrategies = [];


    /**
     * Set the version to test against
     *
     * @param string $version
     * @return Excluder
     */
    public function setVersion(?string $version): Excluder
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set an integer representing the property modifiers that should be excluded
     *
     * @param int $modifiers
     * @return Excluder
     */
    public function setExcludedModifiers(int $modifiers): Excluder
    {
        $this->excludedModifiers = $modifiers;

        return $this;
    }

    /**
     * Require the [@see Expose] annotation to serialize properties
     *
     * @param bool $requireExpose
     * @return Excluder
     */
    public function setRequireExpose(bool $requireExpose): Excluder
    {
        $this->requireExpose = $requireExpose;

        return $this;
    }

    /**
     * Add an exclusion strategy and specify if it should be used during serialization or deserialization
     *
     * @param ExclusionStrategy $strategy
     * @param bool $serialization
     * @param bool $deserialization
     * @return void
     */
    public function addExclusionStrategy(ExclusionStrategy $strategy, bool $serialization, bool $deserialization): void
    {
        if ($serialization) {
            $this->serializationStrategies[] = $strategy;
        }

        if ($deserialization) {
            $this->deserializationStrategies[] = $strategy;
        }
    }

    /**
     * Compile time exclusion checking of classes
     *
     * @param ClassMetadata $classMetadata
     * @param bool $serialize
     * @return bool
     */
    public function excludeClass(ClassMetadata $classMetadata, bool $serialize): bool
    {
        return $this->excludeByAnnotation($classMetadata->getAnnotations(), $serialize);
    }

    /**
     * Runtime exclusion checking of classes by strategy during serialization
     *
     * Uses user-defined strategies
     *
     * @param ClassMetadata $classMetadata
     * @param ExclusionData $exclusionData
     * @return bool
     */
    public function excludeClassBySerializationStrategy(ClassMetadata $classMetadata, ExclusionData $exclusionData): bool
    {
        foreach ($this->serializationStrategies as $exclusionStrategy) {
            if ($exclusionStrategy->shouldSkipClass($classMetadata, $exclusionData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runtime exclusion checking of classes by strategy during deserialization
     *
     * Uses user-defined strategies
     *
     * @param ClassMetadata $classMetadata
     * @param ExclusionData $exclusionData
     * @return bool
     */
    public function excludeClassByDeserializationStrategy(ClassMetadata $classMetadata, ExclusionData $exclusionData): bool
    {
        foreach ($this->deserializationStrategies as $exclusionStrategy) {
            if ($exclusionStrategy->shouldSkipClass($classMetadata, $exclusionData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compile time exclusion checking of properties
     *
     * @param PropertyMetadata $propertyMetadata
     * @param bool $serialize
     * @return bool
     */
    public function excludeProperty(PropertyMetadata $propertyMetadata, bool $serialize): bool
    {
        // exclude the property if the property modifiers are found in the excluded modifiers
        if (0 !== ($this->excludedModifiers & $propertyMetadata->getModifiers())) {
            return true;
        }

        return $this->excludeByAnnotation($propertyMetadata->getAnnotations(), $serialize);
    }

    /**
     * Runtime exclusion checking of properties by strategy during serialization
     *
     * Uses user-defined strategies
     *
     * @param PropertyMetadata $property
     * @param ExclusionData $exclusionData
     * @return bool
     */
    public function excludePropertyBySerializationStrategy(PropertyMetadata $property, ExclusionData $exclusionData): bool
    {
        foreach ($this->serializationStrategies as $exclusionStrategy) {
            if ($exclusionStrategy->shouldSkipProperty($property, $exclusionData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runtime exclusion checking of properties by strategy during deserialization
     *
     * Uses user-defined strategies
     *
     * @param PropertyMetadata $property
     * @param ExclusionData $exclusionData
     * @return bool
     */
    public function excludePropertyByDeserializationStrategy(PropertyMetadata $property, ExclusionData $exclusionData): bool
    {
        foreach ($this->deserializationStrategies as $exclusionStrategy) {
            if ($exclusionStrategy->shouldSkipProperty($property, $exclusionData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if serialization strategies exist
     *
     * @return bool
     */
    public function hasSerializationStrategies(): bool
    {
        return $this->serializationStrategies !== [];
    }

    /**
     * Returns true if deserialization strategies exist
     *
     * @return bool
     */
    public function hasDeserializationStrategies(): bool
    {
        return $this->deserializationStrategies !== [];
    }

    /**
     * Checks various annotations to see if the property should be excluded
     *
     * - [@see Since] / [@see Until]
     * - [@see Exclude]
     * - [@see Expose] (if requireExpose is set)
     *
     * @param AnnotationCollection $annotations
     * @param bool $serialize
     * @return bool
     */
    private function excludeByAnnotation(AnnotationCollection $annotations, bool $serialize): bool
    {
        if (!$this->validVersion($annotations)) {
            return true;
        }

        /** @var Exclude $exclude */
        $exclude = $annotations->get(Exclude::class);
        if (null !== $exclude && $exclude->shouldExclude($serialize)) {
            return true;
        }

        // if we need an expose annotation
        if ($this->requireExpose) {
            /** @var Expose $expose */
            $expose = $annotations->get(Expose::class);
            if (null === $expose || !$expose->shouldExpose($serialize)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the set version is valid for [@see Since] and [@see Until] annotations
     *
     * @param AnnotationCollection $annotations
     * @return bool
     */
    private function validVersion(AnnotationCollection $annotations): bool
    {
        return !$this->shouldSkipSince($annotations) && !$this->shouldSkipUntil($annotations);
    }

    /**
     * Returns true if we should skip based on the [@see Since] annotation
     *
     * @param AnnotationCollection $annotations
     * @return bool
     */
    private function shouldSkipSince(AnnotationCollection $annotations): bool
    {
        $sinceAnnotation = $annotations->get(Since::class);

        return
            null !== $sinceAnnotation
            && null !== $this->version
            && \version_compare($this->version, $sinceAnnotation->getValue(), '<');
    }

    /**
     * Returns true if we should skip based on the [@see Until] annotation
     *
     * @param AnnotationCollection $annotations
     * @return bool
     */
    private function shouldSkipUntil(AnnotationCollection $annotations): bool
    {
        $untilAnnotation = $annotations->get(Until::class);

        return
            null !== $untilAnnotation
            && null !== $this->version
            && \version_compare($this->version, $untilAnnotation->getValue(), '>=');
    }
}
